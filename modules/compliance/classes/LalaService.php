<?php

require_once __DIR__ . '/LalaNormalizer.php';
require_once __DIR__ . '/LalaIntentDetector.php';
require_once __DIR__ . '/LalaContextManager.php';
require_once __DIR__ . '/LalaResponseBuilder.php';
require_once __DIR__ . '/LalaConversationRepository.php';
require_once __DIR__ . '/LalaQuestionPatternRepository.php';
require_once __DIR__ . '/LalaPolicyMatcher.php';
require_once __DIR__ . '/LalaLaborLawMatcher.php';

class LalaService
{
    private $conn;
    private $normalizer;
    private $intentDetector;
    private $contextManager;
    private $policyMatcher;
    private $laborLawMatcher;
    private $responseBuilder;
    private $conversationRepo;
    private $patternRepo;

    public function __construct(PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }

        $this->normalizer = new LalaNormalizer();
        $this->intentDetector = new LalaIntentDetector();
        $this->contextManager = new LalaContextManager();
        $this->policyMatcher = new LalaPolicyMatcher($this->conn);
        $this->laborLawMatcher = new LalaLaborLawMatcher($this->conn);
        $this->responseBuilder = new LalaResponseBuilder();
        $this->conversationRepo = new LalaConversationRepository($this->conn);
        $this->patternRepo = new LalaQuestionPatternRepository($this->conn);
    }

    public function handleQuery(string $rawQuery, array $clientContext = []): array
    {
        $normalizedQuery = $this->normalizer->normalize($rawQuery);
        $intentResult = $this->intentDetector->detect($rawQuery, $clientContext);
        $intent = $intentResult['intent'];
        $confidence = $intentResult['confidence'];

        $context = $this->contextManager->getContext();
        $sessionId = $this->getSessionId();
        $userId = $this->getCurrentUserId();

        $response = [
            'success' => true,
            'type' => 'results',
            'message' => '',
            'results' => [],
            'context' => $context,
            'intent' => $intent,
            'confidence' => $confidence,
            'source' => '',
        ];

        $normalizedQuery = $this->buildEffectiveQuery($normalizedQuery, $context);

        if ($intent === 'GREETING') {
            $response['type'] = 'greeting';
            $response['message'] = $this->responseBuilder->buildGreeting($this->getCurrentUserFirstName());
            $response['options'] = $this->responseBuilder->getQuickReplies('greeting');
            $response['source'] = '';
            $this->contextManager->reset();
            $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, $intent, '', null, $response['message'], $confidence);
            return $response;
        }

        if ($intent === 'GOODBYE') {
            $response['type'] = 'goodbye';
            $response['message'] = 'Goodbye! Feel free to come back anytime you have HR or workplace questions. 👋';
            $response['options'] = [];
            $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, $intent, '', null, $response['message'], $confidence);
            return $response;
        }

        if ($intent === 'HELP') {
            $response['type'] = 'help';
            $response['message'] = "I'm Lala AI, your HR Compliance Assistant. You can ask me about:\n\n• <strong>Labor Law References</strong> — Philippine labor laws, DOLE issuances, and legal requirements\n• <strong>HR Policies</strong> — Company policies on remote work, safety, data privacy, and more\n\nJust describe what you need in plain language.";
            $response['options'] = $this->responseBuilder->getQuickReplies('help');
            $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, $intent, '', null, $response['message'], $confidence);
            return $response;
        }

        if ($intent === 'THANKS') {
            $response['type'] = 'thanks';
            $response['message'] = "You're welcome! Is there anything else I can help you with?";
            $response['options'] = $this->responseBuilder->getQuickReplies('help');
            $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, $intent, '', null, $response['message'], $confidence);
            return $response;
        }

        $followUpIntent = $this->handleFollowUp($intent, $normalizedQuery, $context);
        if ($followUpIntent) {
            return $followUpIntent;
        }

        $specificPolicyIntents = [
            'REMOTE_WORK', 'CODE_OF_CONDUCT', 'DATA_PRIVACY', 'IT_SECURITY',
            'EMPLOYEE_HANDBOOK', 'ANTI_HARASSMENT', 'SOCIAL_MEDIA',
            'BUSINESS_CONTINUITY', 'WORKPLACE_SAFETY', 'EXPENSE_REIMBURSEMENT',
        ];

        if (in_array($intent, $specificPolicyIntents, true)) {
            return $this->handleSpecificPolicyIntent($intent, $rawQuery, $normalizedQuery, $sessionId, $userId, $confidence);
        }

        if ($intent === 'POLICY_SEARCH' || $intent === 'POLICY_DETAILS') {
            return $this->handlePolicySearch($rawQuery, $normalizedQuery, $sessionId, $userId, $confidence);
        }

        if ($intent === 'LABOR_LAW_SEARCH') {
            return $this->handleLaborLawSearch($rawQuery, $normalizedQuery, $sessionId, $userId, $confidence);
        }

        if ($intent === 'UNKNOWN') {
            $response['type'] = 'unknown';
            $response['message'] = $this->responseBuilder->buildUnknownMessage($normalizedQuery);
            $response['options'] = $this->responseBuilder->getQuickReplies('unknown');
            $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, $intent, '', null, $response['message'], $confidence);
            return $response;
        }

        $combinedResult = $this->handleCombinedSearch($rawQuery, $normalizedQuery, $sessionId, $userId, $confidence);
        if ($combinedResult) {
            return $combinedResult;
        }

        $response['type'] = 'unknown';
        $response['message'] = $this->responseBuilder->buildUnknownMessage($normalizedQuery);
        $response['options'] = $this->responseBuilder->getQuickReplies('unknown');
        $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, $intent, '', null, $response['message'], $confidence);
        return $response;
    }

    private function handleFollowUp(string $intent, string $normalizedQuery, array $context): ?array
    {
        if ($intent !== 'FOLLOW_UP' || empty($context['last_source'])) {
            return null;
        }

        $sessionId = $this->getSessionId();
        $userId = $this->getCurrentUserId();

        if ($context['last_source'] === 'policy' && !empty($context['last_policy_id'])) {
            $policy = $this->policyMatcher->getPolicyById((int) $context['last_policy_id']);
            if (!$policy) {
                return null;
            }

            $lower = strtolower($normalizedQuery);
            if (str_contains($lower, 'effective') || str_contains($lower, 'when did') || str_contains($lower, 'when is it') || str_contains($lower, 'when does it start')) {
                $response = [
                    'success' => true,
                    'type' => 'follow_up',
                    'message' => "The <strong>{$policy['title']}</strong> is effective " . $this->formatDate($policy['effective_date'] ?? null) . ".\n\n<strong>Source:</strong> HR Policy — {$policy['title']}",
                    'options' => $this->responseBuilder->getQuickReplies('follow_up', $context),
                    'context' => $context,
                ];
                $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'policy', $policy['id'], $response['message'], 'HIGH');
                return $response;
            }

            if (str_contains($lower, 'version')) {
                $response = [
                    'success' => true,
                    'type' => 'follow_up',
                    'message' => "The <strong>{$policy['title']}</strong> is currently at <strong>Version {$policy['version']}</strong>.\n\n<strong>Source:</strong> HR Policy — {$policy['title']}",
                    'options' => $this->responseBuilder->getQuickReplies('follow_up', $context),
                    'context' => $context,
                ];
                $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'policy', $policy['id'], $response['message'], 'HIGH');
                return $response;
            }

            if (str_contains($lower, 'status') || str_contains($lower, 'published') || str_contains($lower, 'active') || str_contains($lower, 'current')) {
                $response = [
                    'success' => true,
                    'type' => 'follow_up',
                    'message' => "The <strong>{$policy['title']}</strong> is currently marked <strong>{$policy['status']}</strong> in the HRMS record.\n\n<strong>Source:</strong> HR Policy — {$policy['title']}",
                    'options' => $this->responseBuilder->getQuickReplies('follow_up', $context),
                    'context' => $context,
                ];
                $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'policy', $policy['id'], $response['message'], 'HIGH');
                return $response;
            }

            if (str_contains($lower, 'policy code') || str_contains($lower, 'code for this') || str_contains($lower, 'reference number')) {
                $code = (string) ($policy['policy_code'] ?? 'N/A');
                $response = [
                    'success' => true,
                    'type' => 'follow_up',
                    'message' => "The policy code for <strong>{$policy['title']}</strong> is <strong>{$code}</strong>.\n\n<strong>Source:</strong> HR Policy — {$policy['title']}",
                    'options' => $this->responseBuilder->getQuickReplies('follow_up', $context),
                    'context' => $context,
                ];
                $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'policy', $policy['id'], $response['message'], 'HIGH');
                return $response;
            }

            if (str_contains($lower, 'acknowledgement') || str_contains($lower, 'acknowledgment') || str_contains($lower, 'deadline') || str_contains($lower, 'due date')) {
                $ackDeadline = $this->formatDate($policy['acknowledgement_deadline'] ?? null);
                $requiresAck = !empty($policy['requires_acknowledgement']);
                $ackNote = $requiresAck ? 'This policy requires acknowledgement.' : 'This policy does not require acknowledgement.';
                $response = [
                    'success' => true,
                    'type' => 'follow_up',
                    'message' => "For <strong>{$policy['title']}</strong>:" . ($ackDeadline ? "\n\n<strong>Acknowledgement Deadline:</strong> {$ackDeadline}" : "\n\n<strong>Acknowledgement Deadline:</strong> Not set") . "\n\n{$ackNote}\n\n<strong>Source:</strong> HR Policy — {$policy['title']}",
                    'options' => $this->responseBuilder->getQuickReplies('follow_up', $context),
                    'context' => $context,
                ];
                $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'policy', $policy['id'], $response['message'], 'HIGH');
                return $response;
            }

            if (str_contains($lower, 'attachment') || str_contains($lower, 'download') || str_contains($lower, 'document') || str_contains($lower, 'pdf')) {
                $hasAttachment = !empty($policy['attachment_path']);
                $attachmentName = htmlspecialchars($policy['attachment_name'] ?? 'the policy document', ENT_QUOTES, 'UTF-8');
                $response = [
                    'success' => true,
                    'type' => 'follow_up',
                    'message' => $hasAttachment
                        ? "The <strong>{$policy['title']}</strong> has an attached document (<strong>{$attachmentName}</strong>). You can download it from the policy details page.\n\n<strong>Source:</strong> HR Policy — {$policy['title']}"
                        : "The <strong>{$policy['title']}</strong> does not have an attachment available in the HRMS record.\n\n<strong>Source:</strong> HR Policy — {$policy['title']}",
                    'options' => $this->responseBuilder->getQuickReplies('follow_up', $context),
                    'context' => $context,
                ];
                $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'policy', $policy['id'], $response['message'], 'HIGH');
                return $response;
            }

            if (str_contains($lower, 'detail') || str_contains($lower, 'more') || str_contains($lower, 'full') || str_contains($lower, 'content') || str_contains($lower, 'read') || str_contains($lower, 'show')) {
                $message = $this->responseBuilder->buildPolicyDetail($policy);
                $response = [
                    'success' => true,
                    'type' => 'policy_details',
                    'message' => $message,
                    'results' => [['record' => $policy, 'match_type' => 'context']],
                    'options' => $this->responseBuilder->getQuickReplies('policy_result', $context),
                    'context' => $context,
                ];
                $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'policy', $policy['id'], $message, 'HIGH');
                return $response;
            }
        }

        if ($context['last_source'] === 'labor_law' && !empty($context['last_query'])) {
            $lower = strtolower($normalizedQuery);
            $policyWords = ['policy', 'policies', 'handbook', 'company policy', 'hr policy', 'internal policy'];
            $hasPolicyWord = false;
            foreach ($policyWords as $w) {
                if (str_contains($lower, $w)) {
                    $hasPolicyWord = true;
                    break;
                }
            }

            if ($hasPolicyWord) {
                $policyResults = $this->policyMatcher->search($context['last_query'] . ' ' . $normalizedQuery, 5);
                if (!empty($policyResults)) {
                    $bestMatch = $policyResults[0];
                    $policy = $bestMatch['record'];
                    $message = $this->responseBuilder->buildPolicyResult($policy, $bestMatch['match_type']);
                    $newContext = [
                        'last_intent' => 'POLICY_SEARCH',
                        'last_policy_id' => (int) $policy['id'],
                        'last_policy_code' => (string) ($policy['policy_code'] ?? ''),
                        'last_source' => 'policy',
                        'last_query' => $context['last_query'],
                        'topic' => null,
                        'specifics' => [],
                    ];
                    $this->contextManager->setContext($newContext);
                    $response = [
                        'success' => true,
                        'type' => 'policy_result',
                        'message' => "I also found a related HR policy:\n\n" . $message,
                        'results' => $policyResults,
                        'options' => $this->responseBuilder->getQuickReplies('policy_result', $newContext),
                        'context' => $newContext,
                    ];
                    $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'policy', (int) $policy['id'], $response['message'], 'MEDIUM');
                    return $response;
                }
            }

            $lawResults = $this->laborLawMatcher->search($context['last_query'] . ' ' . $normalizedQuery, 3);
            if (!empty($lawResults)) {
                $ref = $lawResults[0]['record'];
                $message = $this->responseBuilder->buildLaborLawResult($ref);
                $response = [
                    'success' => true,
                    'type' => 'results',
                    'message' => $message,
                    'results' => $lawResults,
                    'options' => $this->responseBuilder->getQuickReplies('labor_law_result', $context),
                    'context' => $context,
                ];
                $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'labor_law', $ref['id'], $message, 'MEDIUM');
                return $response;
            }
        }

        if ($context['last_source'] === 'policy' && !empty($context['last_policy_id'])) {
            $policy = $this->policyMatcher->getPolicyById((int) $context['last_policy_id']);
            if (!$policy) {
                return null;
            }

            $lower = strtolower($normalizedQuery);
            $lawWords = ['labor law', 'law says', 'legal requirement', 'statute', 'act of', 'republic act', 'presidential decree', 'department order', 'labor code', 'philippine law', 'dole', 'irr'];
            $hasLawWord = false;
            foreach ($lawWords as $w) {
                if (str_contains($lower, $w)) {
                    $hasLawWord = true;
                    break;
                }
            }

            if ($hasLawWord) {
                $lawResults = $this->laborLawMatcher->search($context['last_query'] . ' ' . $normalizedQuery, 5);
                if (!empty($lawResults)) {
                    $bestMatch = $lawResults[0];
                    $ref = $bestMatch['record'];
                    $message = $this->responseBuilder->buildLaborLawResult($ref);
                    $newContext = [
                        'last_intent' => 'LABOR_LAW_SEARCH',
                        'last_policy_id' => null,
                        'last_policy_code' => null,
                        'last_source' => 'labor_law',
                        'last_query' => $context['last_query'],
                        'topic' => null,
                        'specifics' => [],
                    ];
                    $this->contextManager->setContext($newContext);
                    $response = [
                        'success' => true,
                        'type' => 'results',
                        'message' => "I also found a related Labor Law Reference:\n\n" . $message,
                        'results' => $lawResults,
                        'options' => $this->responseBuilder->getQuickReplies('labor_law_result', $newContext),
                        'context' => $newContext,
                    ];
                    $this->logConversation($sessionId, $userId, $normalizedQuery, $normalizedQuery, 'FOLLOW_UP', 'labor_law', (int) ($ref['id'] ?? 0), $message, 'MEDIUM');
                    return $response;
                }
            }
        }

        return null;
    }

    private function handleSpecificPolicyIntent(string $intent, string $rawQuery, string $normalizedQuery, string $sessionId, ?int $userId, string $confidence): array
    {
        $policyKeywordMap = [
            'REMOTE_WORK' => 'remote work',
            'CODE_OF_CONDUCT' => 'code of conduct',
            'DATA_PRIVACY' => 'data privacy',
            'IT_SECURITY' => 'it security',
            'EMPLOYEE_HANDBOOK' => 'employee handbook',
            'ANTI_HARASSMENT' => 'anti harassment',
            'SOCIAL_MEDIA' => 'social media',
            'BUSINESS_CONTINUITY' => 'business continuity',
            'WORKPLACE_SAFETY' => 'workplace safety',
            'EXPENSE_REIMBURSEMENT' => 'expense reimbursement',
        ];

        $searchTerm = $policyKeywordMap[$intent] ?? $intent;
        $results = $this->policyMatcher->search($searchTerm, 5);

        if (empty($results)) {
            $response = [
                'success' => true,
                'type' => 'no_results',
                'message' => "I couldn't find a matching HR policy for {$searchTerm} in the available records.\n\nYou can try using different keywords or ask me about a different HR topic.",
                'options' => $this->responseBuilder->getQuickReplies('unknown'),
                'context' => $this->contextManager->defaultContext(),
            ];
            $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, $intent, '', null, $response['message'], 'LOW');
            return $response;
        }

        $bestMatch = $results[0];
        $policy = $bestMatch['record'];
        $message = $this->responseBuilder->buildPolicyResult($policy, $bestMatch['match_type']);

        $context = [
            'last_intent' => $intent,
            'last_policy_id' => (int) $policy['id'],
            'last_policy_code' => (string) ($policy['policy_code'] ?? ''),
            'last_source' => 'policy',
            'last_query' => $searchTerm,
            'topic' => strtolower(str_replace('_', ' ', $intent)),
            'specifics' => [],
        ];
        $this->contextManager->setContext($context);

        $response = [
            'success' => true,
            'type' => 'policy_result',
            'message' => $message,
            'results' => $results,
            'options' => $this->responseBuilder->getQuickReplies('policy_result', $context),
            'context' => $context,
        ];

        $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, $intent, 'policy', (int) $policy['id'], $message, $confidence);
        $this->patternRepo->findOrCreate($normalizedQuery, $intent, 'policy');

        return $response;
    }

    private function handlePolicySearch(string $rawQuery, string $normalizedQuery, string $sessionId, ?int $userId, string $confidence): array
    {
        $results = $this->policyMatcher->search($normalizedQuery, 5);

        if (empty($results)) {
            $response = [
                'success' => true,
                'type' => 'no_results',
                'message' => "I couldn't find a closely matching HR policy in the available records.\n\nYou can try using different keywords or ask me about a specific HR topic.",
                'options' => $this->responseBuilder->getQuickReplies('unknown'),
                'context' => $this->contextManager->defaultContext(),
            ];
            $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, 'POLICY_SEARCH', '', null, $response['message'], 'LOW');
            return $response;
        }

        $bestMatch = $results[0];
        $policy = $bestMatch['record'];
        $message = $this->responseBuilder->buildPolicyResult($policy, $bestMatch['match_type']);

        $context = [
            'last_intent' => 'POLICY_SEARCH',
            'last_policy_id' => (int) $policy['id'],
            'last_policy_code' => (string) ($policy['policy_code'] ?? ''),
            'last_source' => 'policy',
            'last_query' => $normalizedQuery,
            'topic' => null,
            'specifics' => [],
        ];
        $this->contextManager->setContext($context);

        $response = [
            'success' => true,
            'type' => 'policy_result',
            'message' => $message,
            'results' => $results,
            'options' => $this->responseBuilder->getQuickReplies('policy_result', $context),
            'context' => $context,
        ];

        $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, 'POLICY_SEARCH', 'policy', (int) $policy['id'], $message, $confidence);
        $this->patternRepo->findOrCreate($normalizedQuery, 'POLICY_SEARCH', 'policy');

        return $response;
    }

    private function handleLaborLawSearch(string $rawQuery, string $normalizedQuery, string $sessionId, ?int $userId, string $confidence): array
    {
        $results = $this->laborLawMatcher->search($normalizedQuery, 5);

        if (empty($results)) {
            $response = [
                'success' => true,
                'type' => 'no_results',
                'message' => "I couldn't find a closely matching labor law reference in the current HRMS database.\n\nYou can describe the situation in more detail and I'll try to narrow it down.",
                'options' => $this->responseBuilder->getQuickReplies('unknown'),
                'context' => $this->contextManager->defaultContext(),
            ];
            $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, 'LABOR_LAW_SEARCH', '', null, $response['message'], 'LOW');
            return $response;
        }

        $bestMatch = $results[0];
        $ref = $bestMatch['record'];
        $message = $this->responseBuilder->buildLaborLawResult($ref);

        $context = [
            'last_intent' => 'LABOR_LAW_SEARCH',
            'last_policy_id' => null,
            'last_policy_code' => null,
            'last_source' => 'labor_law',
            'last_query' => $normalizedQuery,
            'topic' => null,
            'specifics' => [],
        ];
        $this->contextManager->setContext($context);

        $response = [
            'success' => true,
            'type' => 'results',
            'message' => $message,
            'results' => $results,
            'options' => $this->responseBuilder->getQuickReplies('labor_law_result', $context),
            'context' => $context,
        ];

        $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, 'LABOR_LAW_SEARCH', 'labor_law', (int) ($ref['id'] ?? 0), $message, $confidence);
        $this->patternRepo->findOrCreate($normalizedQuery, 'LABOR_LAW_SEARCH', 'labor_law');

        return $response;
    }

    private function handleCombinedSearch(string $rawQuery, string $normalizedQuery, string $sessionId, ?int $userId, string $confidence): ?array
    {
        $lower = strtolower($normalizedQuery);
        $needsBoth = (
            str_contains($lower, 'labor law') && str_contains($lower, 'policy') ||
            str_contains($lower, 'law says') && str_contains($lower, 'policy') ||
            str_contains($lower, 'legal') && str_contains($lower, 'hr policy') ||
            str_contains($lower, 'law') && str_contains($lower, 'company policy') ||
            str_contains($lower, 'law') && str_contains($lower, 'internal policy') ||
            str_contains($lower, 'statute') && str_contains($lower, 'policy') ||
            str_contains($lower, 'republic act') && str_contains($lower, 'policy') ||
            str_contains($lower, 'presidential decree') && str_contains($lower, 'policy') ||
            str_contains($lower, 'department order') && str_contains($lower, 'policy') ||
            str_contains($lower, 'legal requirement') && str_contains($lower, 'policy') ||
            str_contains($lower, 'labor standard') && str_contains($lower, 'policy') ||
            str_contains($lower, 'law') && str_contains($lower, 'our policy')
        );

        if (!$needsBoth) {
            return null;
        }

        $lawResults = $this->laborLawMatcher->search($normalizedQuery, 3);
        $policyResults = $this->policyMatcher->search($normalizedQuery, 3);

        if (empty($lawResults) && empty($policyResults)) {
            return null;
        }

        $messageParts = [];
        $allResults = [];

        if (!empty($lawResults)) {
            $messageParts[] = $this->responseBuilder->buildLaborLawResult($lawResults[0]['record']);
            $allResults = array_merge($allResults, $lawResults);
        }

        if (!empty($policyResults)) {
            $messageParts[] = $this->responseBuilder->buildPolicyResult($policyResults[0]['record'], 'combined');
            $allResults = array_merge($allResults, $policyResults);
        }

        $message = implode("\n\n" . str_repeat('=', 30) . "\n\n", $messageParts);
        $context = [
            'last_intent' => 'COMBINED_SEARCH',
            'last_policy_id' => !empty($policyResults) ? (int) $policyResults[0]['record']['id'] : null,
            'last_policy_code' => !empty($policyResults) ? (string) ($policyResults[0]['record']['policy_code'] ?? '') : null,
            'last_source' => 'combined',
            'last_query' => $normalizedQuery,
            'topic' => null,
            'specifics' => [],
        ];
        $this->contextManager->setContext($context);

        $response = [
            'success' => true,
            'type' => 'results',
            'message' => $message,
            'results' => $allResults,
            'options' => $this->responseBuilder->getQuickReplies('policy_result', $context),
            'context' => $context,
        ];

        $this->logConversation($sessionId, $userId, $rawQuery, $normalizedQuery, 'COMBINED_SEARCH', 'combined', null, $message, $confidence);
        return $response;
    }

    private function logConversation(string $sessionId, ?int $userId, string $question, string $normalizedQuestion, string $intent, string $source, ?int $matchedRecordId, string $response, string $confidence): void
    {
        try {
            $this->conversationRepo->log(compact(
                'sessionId', 'userId', 'question', 'normalizedQuestion', 'intent', 'source', 'matchedRecordId', 'response', 'confidence'
            ));
        } catch (Exception $e) {
            error_log('Lala AI conversation log error: ' . $e->getMessage());
        }
    }

    private function getSessionId(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return session_id();
    }

    private function getCurrentUserId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : null;
    }

    private function getCurrentUserFirstName(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $name = trim((string) ($_SESSION['employee_name'] ?? 'there'));
        $parts = explode(' ', $name);
        return $parts[0] ?: 'there';
    }

    private function buildEffectiveQuery(string $query, array $context): string
    {
        $parts = [$query];

        if (!empty($context['last_query']) && !str_contains(strtolower($query), strtolower($context['last_query']))) {
            $parts[] = $context['last_query'];
        }

        if (!empty($context['topic']) && !str_contains(strtolower($query), strtolower($context['topic']))) {
            $parts[] = $context['topic'];
        }

        if (!empty($context['specifics']) && is_array($context['specifics'])) {
            foreach ($context['specifics'] as $specific) {
                if (!str_contains(strtolower($query), strtolower($specific))) {
                    $parts[] = $specific;
                }
            }
        }

        return implode(' ', array_unique(array_filter($parts)));
    }

    private function formatDate(?string $date): string
    {
        if (!$date) return '';
        try {
            $dt = new DateTime($date);
            return $dt->format('F j, Y');
        } catch (Exception $e) {
            return htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
        }
    }
}
