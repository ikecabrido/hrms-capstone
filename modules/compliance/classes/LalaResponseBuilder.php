<?php

require_once __DIR__ . '/LalaNormalizer.php';

class LalaResponseBuilder
{
    private $normalizer;

    public function __construct()
    {
        $this->normalizer = new LalaNormalizer();
    }

    public function buildGreeting(string $firstName): string
    {
        $name = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        return "Hi, {$name}! 👋\n\nI'm <strong>Lala AI</strong>, your HR Compliance Assistant.\n\nI can help you find information from:\n• Labor Law References\n• HR Policies\n\nJust describe what you need in your own words.";
    }

    public function buildPolicyResult(array $policy, string $matchType): string
    {
        $status = (string) ($policy['status'] ?? 'Unknown');
        $title = htmlspecialchars($policy['title'] ?? 'Untitled Policy', ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars($policy['policy_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $version = htmlspecialchars($policy['version'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $effective = $this->formatDate($policy['effective_date'] ?? null);
        $description = htmlspecialchars($policy['description'] ?? '', ENT_QUOTES, 'UTF-8');

        $statusNote = '';
        $statusUpper = strtoupper($status);
        if ($statusUpper === 'DRAFT') {
            $statusNote = "\n\n⚠️ <em>Note: This policy is currently marked <strong>Draft</strong> and may not represent the final approved HR policy.</em>";
        } elseif ($statusUpper === 'FOR REVIEW') {
            $statusNote = "\n\n⚠️ <em>Note: This policy is currently marked <strong>For Review</strong> and is not yet finalized.</em>";
        } elseif ($statusUpper === 'ARCHIVED') {
            $statusNote = "\n\n⚠️ <em>Note: This policy is currently marked <strong>Archived</strong>. A newer Published policy may exist.</em>";
        }

        $sourceLabel = match ($statusUpper) {
            'PUBLISHED' => 'HR Policy (Published)',
            default => "HR Policy ({$status})",
        };

        $response = "I found a matching HR policy in the system:\n\n";
        $response .= "<strong>{$title}</strong>\n\n";
        $response .= "<strong>Policy Code:</strong> {$code}\n";
        $response .= "<strong>Status:</strong> {$status}\n";
        $response .= "<strong>Version:</strong> {$version}\n";
        if ($effective) {
            $response .= "<strong>Effective Date:</strong> {$effective}\n";
        }
        $response .= "\n";
        if ($description) {
            $response .= "{$description}\n\n";
        }
        $response .= $statusNote;
        $response .= "\n\n<strong>Source:</strong> {$sourceLabel}";

        return $response;
    }

    public function buildPolicyDetail(array $policy): string
    {
        $status = (string) ($policy['status'] ?? 'Unknown');
        $title = htmlspecialchars($policy['title'] ?? 'Untitled Policy', ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars($policy['policy_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($policy['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $content = htmlspecialchars($policy['content'] ?? '', ENT_QUOTES, 'UTF-8');
        $version = htmlspecialchars($policy['version'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $effective = $this->formatDate($policy['effective_date'] ?? null);
        $ackDeadline = $this->formatDate($policy['acknowledgement_deadline'] ?? null);
        $requiresAck = !empty($policy['requires_acknowledgement']);
        $hasAttachment = !empty($policy['attachment_path']);
        $category = htmlspecialchars($policy['category_name'] ?? '', ENT_QUOTES, 'UTF-8');

        $statusUpper = strtoupper($status);
        $statusNote = '';
        if ($statusUpper === 'DRAFT') {
            $statusNote = "\n\n⚠️ <em>Note: This policy is currently marked <strong>Draft</strong>.</em>";
        } elseif ($statusUpper === 'FOR REVIEW') {
            $statusNote = "\n\n⚠️ <em>Note: This policy is currently marked <strong>For Review</strong>.</em>";
        } elseif ($statusUpper === 'ARCHIVED') {
            $statusNote = "\n\n⚠️ <em>Note: This policy is currently marked <strong>Archived</strong>.</em>";
        }

        $response = "<strong>{$title}</strong>\n\n";
        $response .= "<strong>Policy Code:</strong> {$code}\n";
        $response .= "<strong>Status:</strong> {$status}\n";
        $response .= "<strong>Version:</strong> {$version}\n";
        if ($category) {
            $response .= "<strong>Category:</strong> {$category}\n";
        }
        if ($effective) {
            $response .= "<strong>Effective Date:</strong> {$effective}\n";
        }
        if ($ackDeadline) {
            $response .= "<strong>Acknowledgement Deadline:</strong> {$ackDeadline}\n";
        }
        if ($requiresAck) {
            $response .= "<strong>Acknowledgement Required:</strong> Yes\n";
        }
        if ($description) {
            $response .= "\n{$description}\n";
        }
        if ($content) {
            $response .= "\n<strong>Policy Content:</strong>\n\n{$content}\n";
        }
        if ($hasAttachment) {
            $response .= "\n📎 <em>Policy document available for download.</em>\n";
        }
        $response .= $statusNote;
        $response .= "\n\n<strong>Source:</strong> HR Policy — {$title}";

        return $response;
    }

    public function buildLaborLawResult(array $reference): string
    {
        $title = htmlspecialchars($reference['title'] ?? 'Untitled Reference', ENT_QUOTES, 'UTF-8');
        $refNumber = htmlspecialchars($reference['reference_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $refType = htmlspecialchars($reference['reference_type'] ?? '', ENT_QUOTES, 'UTF-8');
        $issuingAuthority = htmlspecialchars($reference['issuing_authority'] ?? '', ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars($reference['status'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $summary = htmlspecialchars($reference['summary'] ?? '', ENT_QUOTES, 'UTF-8');

        $response = "I found a Labor Law Reference in the HRMS:\n\n";
        $response .= "<strong>{$title}</strong>\n\n";
        if ($refNumber) {
            $response .= "<strong>Reference Number:</strong> {$refNumber}\n";
        }
        if ($refType) {
            $response .= "<strong>Type:</strong> {$refType}\n";
        }
        if ($issuingAuthority) {
            $response .= "<strong>Issuing Authority:</strong> {$issuingAuthority}\n";
        }
        $response .= "<strong>Status:</strong> {$status}\n";
        if ($summary) {
            $response .= "\n{$summary}\n";
        }
        $response .= "\n<strong>Source:</strong> Labor Law Reference";

        return $response;
    }

    public function buildCombinedResult(array $policyResult, array $lawResult): string
    {
        $response = "Here's what I found from both sources:\n\n";
        $response .= "<strong>Labor Law Reference</strong>\n";
        $response .= str_repeat('-', 30) . "\n";
        $response .= $this->buildLaborLawResult($lawResult);
        $response .= "\n\n";
        $response .= "<strong>HR Policy</strong>\n";
        $response .= str_repeat('-', 30) . "\n";
        $response .= $this->buildPolicyResult($policyResult, 'combined');
        return $response;
    }

    public function buildUnknownMessage(string $normalizedQuery): string
    {
        return "I'm not finding a reliable match for that in the available Labor Law Reference or HR Policy records in the HRMS.\n\nCould you rephrase your question or tell me the HR topic you're asking about? For example, you can ask about:\n\n• Labor laws (overtime, leave, safety, etc.)\n• Company policies (remote work, data privacy, code of conduct, etc.)";
    }

    public function buildClarificationMessage(string $topic): array
    {
        $messages = [
            'remote_work' => 'Are you asking about remote work policies or the legal requirements for remote work?',
            'data_privacy' => 'Are you asking about data privacy laws or the company\'s internal data privacy policy?',
            'workplace_safety' => 'Are you asking about occupational safety laws or the company\'s internal safety policy?',
            'harassment' => 'Are you asking about anti-harassment laws or the company\'s internal anti-harassment policy?',
        ];

        $labels = [
            'remote_work' => 'Remote work',
            'data_privacy' => 'Data privacy',
            'workplace_safety' => 'Workplace safety',
            'harassment' => 'Harassment',
        ];

        $msg = $messages[$topic] ?? 'Are you asking about the legal requirement or the company\'s internal HR policy?';
        $label = $labels[$topic] ?? 'General';

        return [
            'message' => $msg,
            'topic' => $topic,
            'label' => $label,
        ];
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

    public function getQuickReplies(string $type, array $context = []): array
    {
        $source = $context['last_source'] ?? '';

        if ($type === 'greeting' || $type === 'help') {
            return [
                ['label' => 'Labor Law', 'value' => 'labor law reference'],
                ['label' => 'HR Policies', 'value' => 'hr policies'],
                ['label' => 'Remote Work', 'value' => 'remote work policy'],
                ['label' => 'Workplace Safety', 'value' => 'workplace safety policy'],
                ['label' => 'Data Privacy', 'value' => 'data privacy policy'],
            ];
        }

        if ($type === 'policy_result' || $source === 'policy') {
            return [
                ['label' => 'View details', 'value' => 'tell me more about this policy'],
                ['label' => 'Effective date', 'value' => 'when is it effective'],
                ['label' => 'Policy status', 'value' => 'what is the status'],
                ['label' => 'Related policies', 'value' => 'related policies'],
            ];
        }

        if ($type === 'labor_law_result' || $source === 'labor_law') {
            return [
                ['label' => 'Show details', 'value' => 'show details'],
                ['label' => 'Related references', 'value' => 'related references'],
                ['label' => 'HR Policy', 'value' => 'hr policy on this topic'],
                ['label' => 'Ask another', 'value' => 'ask another question'],
            ];
        }

        if ($type === 'follow_up') {
            return [
                ['label' => 'View details', 'value' => 'tell me more about this policy'],
                ['label' => 'Effective date', 'value' => 'when is it effective'],
                ['label' => 'Policy status', 'value' => 'what is the status'],
            ];
        }

        return [
            ['label' => 'Labor Law', 'value' => 'labor law reference'],
            ['label' => 'HR Policies', 'value' => 'hr policies'],
            ['label' => 'Remote Work', 'value' => 'remote work policy'],
            ['label' => 'Workplace Safety', 'value' => 'workplace safety policy'],
            ['label' => 'Data Privacy', 'value' => 'data privacy policy'],
        ];
    }
}
