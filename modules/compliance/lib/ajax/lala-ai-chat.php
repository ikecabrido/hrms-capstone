<?php

require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../classes/LaborLawReference.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$rawQuery = trim((string) ($input['query'] ?? ''));
$clientContext = is_array($input['context'] ?? null) ? $input['context'] : [];

try {
    if (empty($rawQuery)) {
        echo json_encode([
            'success' => true,
            'type' => 'greeting',
            'message' => 'Please describe the HR or workplace concern you want to search for.',
            'options' => [
                ['label' => 'Labor Law', 'value' => 'labor law reference'],
                ['label' => 'Wages & Salary', 'value' => 'minimum wage concern'],
                ['label' => 'Leave', 'value' => 'leave concern'],
                ['label' => 'Working Hours', 'value' => 'working hours concern'],
                ['label' => 'Government Contributions', 'value' => 'government contributions concern'],
                ['label' => 'Workplace Concern', 'value' => 'workplace concern'],
            ],
            'context' => resetContext(),
        ]);
        exit;
    }

    $db = (new Database())->getConnection();
    $model = new LaborLawReference($db);

    $query = normalizeQuery($rawQuery);
    $lower = strtolower($query);

    $greetings = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'greetings', 'howdy'];
    $isGreeting = in_array($lower, $greetings, true) || (preg_match('/^(hi|hello|hey|good\s+(morning|afternoon|evening))/i', $lower) && str_word_count($lower) <= 3);

    if ($isGreeting) {
        $employeeName = trim((string) ($_SESSION['employee_name'] ?? 'there'));
        $firstName = explode(' ', $employeeName)[0];
        echo json_encode([
            'success' => true,
            'type' => 'greeting',
            'message' => 'Hi, ' . $firstName . '! 👋' . "\n\n" . 'I\'m Lala AI, your Labor Law Reference Assistant.' . "\n\n" . 'Tell me what\'s happening at work, and I\'ll help you find the most relevant labor-law references.' . "\n\n" . 'You can simply describe the situation in your own words.',
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext(),
        ]);
        exit;
    }

    if (isUnrelated($query)) {
        echo json_encode([
            'success' => true,
            'type' => 'unrelated',
            'message' => "I'm designed to help with HR, workplace, and labor-law reference concerns. Try describing an employee or workplace situation instead. For example: \"An employee was injured at work and was not provided proper PPE.\"",
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext(),
        ]);
        exit;
    }

    $ambiguity = detectAmbiguity($query, $clientContext);
    if ($ambiguity) {
        echo json_encode([
            'success' => true,
            'type' => 'clarify',
            'message' => $ambiguity['message'],
            'options' => $ambiguity['options'],
            'context' => $ambiguity['context'] ?? $clientContext,
        ]);
        exit;
    }

    if (isDeveloperQuery($query)) {
        echo json_encode([
            'success' => true,
            'type' => 'developer',
            'message' => getDeveloperInfo(),
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext(),
        ]);
        exit;
    }

    if (isVague($query)) {
        $topic = detectTopic($query);
        echo json_encode([
            'success' => true,
            'type' => 'clarify',
            'message' => getClarificationMessage($topic),
            'options' => getQuickRepliesForTopic($topic),
            'context' => array_merge($clientContext, ['topic' => $topic]),
        ]);
        exit;
    }

    if (isFollowUpClosing($query)) {
        echo json_encode([
            'success' => true,
            'type' => 'goodbye',
            'message' => "Thank you and have a great shift! 👍",
            'options' => [],
            'context' => resetContext(),
        ]);
        exit;
    }

    if (isFollowUpContinue($query)) {
        echo json_encode([
            'success' => true,
            'type' => 'thanks',
            'message' => "Of course! Feel free to describe another concern and I'll help you find the relevant labor-law references.",
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext(),
        ]);
        exit;
    }

    if (isIdleCheckNo($query)) {
        echo json_encode([
            'success' => true,
            'type' => 'goodbye',
            'message' => "Thank you and have a great shift! 👍",
            'options' => [],
            'context' => resetContext(),
        ]);
        exit;
    }

    if (isIdleCheckYes($query)) {
        echo json_encode([
            'success' => true,
            'type' => 'thanks',
            'message' => "Great! Feel free to describe another concern and I'll help you find the relevant labor-law references.",
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext(),
        ]);
        exit;
    }

    $topic = detectTopic($query);
    $effectiveQuery = buildEffectiveQuery($query, $clientContext);
    $results = $model->searchReferencesForAssistant($effectiveQuery, 10);
    $confidenceQuery = $query;

    if ($topic === 'workplace' && empty($results)) {
        $workplaceKeywords = ['harassment', 'discrimination', 'safety', 'occupational'];
        $allResults = [];
        $seenIds = [];

        foreach ($workplaceKeywords as $keyword) {
            $keywordQuery = buildEffectiveQuery($keyword, $clientContext);
            $keywordResults = $model->searchReferencesForAssistant($keywordQuery, 10);
            foreach ($keywordResults as $r) {
                if (!isset($seenIds[$r['id']])) {
                    $allResults[] = $r;
                    $seenIds[$r['id']] = true;
                }
            }
        }

        $results = array_slice($allResults, 0, 10);
        $confidenceQuery = implode(' ', $workplaceKeywords);
    }

    if ($topic === 'working_hours' && empty($results)) {
        $workingHoursKeywords = ['overtime', 'rest day', 'holiday work', 'night shift', 'working hours', 'hours of work'];
        $allResults = [];
        $seenIds = [];

        foreach ($workingHoursKeywords as $keyword) {
            $keywordQuery = buildEffectiveQuery($keyword, $clientContext);
            $keywordResults = $model->searchReferencesForAssistant($keywordQuery, 10);
            foreach ($keywordResults as $r) {
                if (!isset($seenIds[$r['id']])) {
                    $allResults[] = $r;
                    $seenIds[$r['id']] = true;
                }
            }
        }

        $results = array_slice($allResults, 0, 10);
        $confidenceQuery = implode(' ', $workingHoursKeywords);
    }

    if (empty($results)) {
        $fallback = LaborLawReference::getAgencyForQuery($query);
        echo json_encode([
            'success' => true,
            'type' => 'fallback',
            'message' => "I couldn't find a sufficiently specific answer in the LALA knowledge database for your concern.",
            'fallback_agency' => $fallback['agency'],
            'fallback_url' => $fallback['url'],
            'fallback_why' => $fallback['why'],
            'context' => array_merge($clientContext, ['topic' => $topic]),
        ]);
        exit;
    }

    $highMatches = [];
    $partialMatches = [];
    $lowMatches = [];

    foreach ($results as $r) {
        $confidence = evaluateMatchConfidence($confidenceQuery, $r);
        if ($confidence === 'high') {
            $highMatches[] = $r;
        } elseif ($confidence === 'partial') {
            $partialMatches[] = $r;
        } else {
            $lowMatches[] = $r;
        }
    }

    $validHighMatches = array_values(array_filter($highMatches, function ($r) use ($model) {
        return !$model->isLikelyOutdated($r);
    }));

    $needsCurrentInfo = preg_match('/minimum wage|wage order|contribution rate|current rate|current amount|latest|2024|2025|2026/', $lower);

    if (!empty($validHighMatches)) {
        if ($needsCurrentInfo) {
            $allRecent = true;
            foreach ($validHighMatches as $r) {
                if (!empty($r['date_issued'])) {
                    $year = (int) date('Y', strtotime($r['date_issued']));
                    if ($year < 2024) {
                        $allRecent = false;
                        break;
                    }
                }
            }

            if (!$allRecent) {
                $fallback = LaborLawReference::getAgencyForQuery($query);
                echo json_encode([
                    'success' => true,
                    'type' => 'fallback',
                    'message' => "I couldn't find a sufficiently specific and current answer in the LALA knowledge database for your concern.",
                    'fallback_agency' => $fallback['agency'],
                    'fallback_url' => $fallback['url'],
                    'fallback_why' => $fallback['why'] . ' This information may change frequently and should be verified with the official source.',
                    'context' => array_merge($clientContext, ['topic' => $topic]),
                ]);
                exit;
            }
        }

        $formatted = [];
        foreach ($validHighMatches as $r) {
            $formatted[] = [
                'id' => (int) $r['id'],
                'title' => $r['title'] ?? '',
                'short_title' => $r['short_title'] ?? '',
                'reference_number' => $r['reference_number'] ?? '',
                'reference_type' => $r['reference_type'] ?? '',
                'issuing_authority' => $r['issuing_authority'] ?? '',
                'status' => $r['status'] ?? '',
                'source_url' => $r['source_url'] ?? '',
                'matched_concepts' => generateMatchedConcepts($effectiveQuery, $r),
                'category_name' => $r['category_name'] ?? '',
                'summary' => $r['summary'] ?? '',
            ];
        }

        echo json_encode([
            'success' => true,
            'type' => 'results',
            'message' => 'I found references that may relate to your concern:',
            'results' => $formatted,
            'context' => array_merge($clientContext, ['topic' => $topic, 'last_query' => $query]),
            'follow_up' => true,
            'follow_up_message' => "Is there anything else I can help you with?",
            'follow_up_options' => [
                ['label' => 'Yes, I have another question', 'value' => 'yes another question'],
                ['label' => 'No, that\'s all', 'value' => 'no that is all' ],
            ],
        ]);
        exit;
    }

    if (!empty($partialMatches)) {
        $formatted = [];
        foreach ($partialMatches as $r) {
            $formatted[] = [
                'id' => (int) $r['id'],
                'title' => $r['title'] ?? '',
                'short_title' => $r['short_title'] ?? '',
                'reference_number' => $r['reference_number'] ?? '',
                'reference_type' => $r['reference_type'] ?? '',
                'issuing_authority' => $r['issuing_authority'] ?? '',
                'status' => $r['status'] ?? '',
                'source_url' => $r['source_url'] ?? '',
                'matched_concepts' => generateMatchedConcepts($effectiveQuery, $r),
                'category_name' => $r['category_name'] ?? '',
                'summary' => $r['summary'] ?? '',
            ];
        }

        $fallback = LaborLawReference::getAgencyForQuery($query);
        echo json_encode([
            'success' => true,
            'type' => 'partial_match',
            'message' => "I found some related references, but they may not fully address your specific question. Here's what I found in the LALA knowledge database:",
            'results' => $formatted,
            'fallback_agency' => $fallback['agency'],
            'fallback_url' => $fallback['url'],
            'fallback_why' => $fallback['why'],
            'context' => array_merge($clientContext, ['topic' => $topic, 'last_query' => $query]),
            'follow_up' => true,
            'follow_up_message' => "Is there anything else I can help you with?",
            'follow_up_options' => [
                ['label' => 'Yes, I have another question', 'value' => 'yes another question'],
                ['label' => 'No, that\'s all', 'value' => 'no that is all' ],
            ],
        ]);
        exit;
    }

    $fallback = LaborLawReference::getAgencyForQuery($query);
    echo json_encode([
        'success' => true,
        'type' => 'fallback',
        'message' => "I couldn't find a sufficiently specific answer in the LALA knowledge database for your concern.",
        'fallback_agency' => $fallback['agency'],
        'fallback_url' => $fallback['url'],
        'fallback_why' => $fallback['why'],
        'context' => array_merge($clientContext, ['topic' => $topic]),
    ]);
    exit;

} catch (Exception $e) {
    error_log('Labor Law Assistant error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'I couldn\'t check the references right now. Please try again in a moment.']);
    exit;
}

function resetContext(): array {
    return ['topic' => null, 'last_query' => null, 'specifics' => []];
}

function normalizeQuery(string $query): string {
    $map = [
        'minumum' => 'minimum',
        'wagee' => 'wage',
        'wagem' => 'wage',
        'overtyme' => 'overtime',
        'overtim' => 'overtime',
        'overtym' => 'overtime',
        'overtimee' => 'overtime',
        'payy' => 'pay',
        'benifit' => 'benefit',
        'benifits' => 'benefits',
        'bennefit' => 'benefit',
        'maternaty' => 'maternity',
        'materniy' => 'maternity',
        'paternaty' => 'paternity',
        'contibution' => 'contribution',
        'contibutions' => 'contributions',
        'ssscontribution' => 'sss contribution',
        'sss contibution' => 'sss contribution',
        'philheath' => 'philhealth',
        'philhealt' => 'philhealth',
        'pag ibig' => 'pag-ibig',
        'pagibigcontrib' => 'pag-ibig contribution',
        'pag-ibigcontrib' => 'pag-ibig contribution',
        'holidy' => 'holiday',
        'holidayd' => 'holiday',
        'nightdif' => 'night differential',
        'night dif' => 'night differential',
        'nightdifferential' => 'night differential',
        'employe' => 'employee',
        'employye' => 'employee',
        'employer' => 'employer',
        'employeer' => 'employer',
        'employess' => 'employees',
        'workd' => 'worked',
        'workded' => 'worked',
        'res day' => 'rest day',
        'restday' => 'rest day',
        'rest days' => 'rest day',
        'sundae' => 'sunday',
        'sundey' => 'sunday',
        'sunduy' => 'sunday',
    ];

    $q = strtolower($query);
    foreach ($map as $bad => $good) {
        $q = str_replace($bad, $good, $q);
    }

    $q = preg_replace('/\s+/', ' ', $q);
    $q = trim($q);

    if ($q !== strtolower(trim($query))) {
        return $q;
    }

    return trim($query);
}

function buildEffectiveQuery(string $query, array $context): string {
    $parts = [$query];

    if (!empty($context['topic']) && !str_contains(strtolower($query), $context['topic'])) {
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

function isUnrelated(string $query): bool {
    $lower = strtolower($query);
    $unrelated = ['weather', 'stock', 'market', 'sports', 'news', 'movie', 'game', 'temperature', 'crypto', 'bitcoin', 'food', 'recipe', 'travel', 'music', 'song', 'joke', 'funny', 'date', 'time', 'president', 'election', 'politics'];
    foreach ($unrelated as $u) {
        if (str_contains($lower, $u)) {
            return true;
        }
    }
    return false;
}

function isVague(string $query): bool {
    $lower = strtolower($query);
    if ($lower === '') {
        return true;
    }

    $vaguePhrases = [
        'i have a problem', 'my employee has an issue', 'i need help',
        'labor law question', 'there is a problem at work', 'employee concern',
        'what should i do', 'i have a question', 'help me', 'something happened',
        'there is an issue', 'i have an issue', 'employee problem', 'can you help',
        'i want to ask', 'help me with this', 'i have an employee problem',
        'employee issue', 'workplace issue', 'hr concern', 'compliance issue'
    ];
    foreach ($vaguePhrases as $phrase) {
        if (str_contains($lower, $phrase)) {
            return true;
        }
    }

    $wordCount = str_word_count($lower);
    if ($wordCount >= 5) {
        return false;
    }

    $specificWords = [
        'wage', 'salary', 'pay', 'compensation', 'minimum wage', 'overtime',
        'leave', 'maternity', 'paternity', 'solo parent', 'parental', 'vacation',
        'safety', 'injury', 'accident', 'ppe', 'hazard', 'workplace safety',
        'harassment', 'discrimination', 'safe spaces', 'gender', 'violence',
        'sexual', 'termination', 'separation', 'dismissal', 'retirement', 'resignation', 'constructive',
        'contribution', 'sss', 'philhealth', 'pagibig', 'bir', 'government contribution',
        'foreign', 'alien', 'permit', 'contract', 'document', 'requirement',
        'union', 'labor relations', 'collective bargaining', 'certification',
        'privacy', 'data privacy', 'mental health', 'wellness', 'employee',
        'employer', 'work', 'hours', 'night shift', 'holiday pay', '13th month',
        'thirteenth month', 'service incentive', 'retirement pay', 'resignation',
        'constructive dismissal', 'security of tenure', 'probationary', ' regularization ',
        'rest day', 'holiday', 'benefit', 'bonus', 'night differential',
        'sunday', 'saturday', 'rest day work', 'working hours',
        'workplace concern', 'workplace'
    ];

    $hasSpecific = false;
    foreach ($specificWords as $sw) {
        if (str_contains($lower, $sw)) {
            $hasSpecific = true;
            break;
        }
    }

    if (!$hasSpecific) {
        return true;
    }

    $generalTopicValues = [
        'salary concern',
        'leave concern',
        'working hours concern',
        'government contributions concern',
        'benefits concern',
        'labor law reference',
    ];
    if (in_array($lower, $generalTopicValues, true)) {
        return true;
    }

    if ($wordCount < 2) {
        $isSpecificWord = false;
        foreach ($specificWords as $sw) {
            if ($lower === $sw) {
                $isSpecificWord = true;
                break;
            }
        }
        if (!$isSpecificWord) {
            return true;
        }
    }

    return false;
}

function detectAmbiguity(string $query, array $context): ?array {
    $lower = strtolower($query);

    if (preg_match('/employee.*(work|worked).*(sunday|sundae|sundey)/i', $lower) ||
        preg_match('/sunday.*(work|worked).*employee/i', $lower) ||
        preg_match('/work.*sunday/i', $lower)) {
        $topic = $context['topic'] ?? 'working_hours';
        return [
            'message' => 'Got it. I understand this is about an employee working on Sunday.' . "\n\n" . 'Was Sunday their scheduled rest day?',
            'options' => [
                ['label' => 'Yes, it was their rest day', 'value' => 'employee worked on rest day sunday', 'specifics' => ['rest day', 'sunday']],
                ['label' => 'No, it was a regular workday', 'value' => 'employee worked sunday regular workday', 'specifics' => ['regular workday', 'sunday']],
                ['label' => 'I\'m not sure', 'value' => 'employee worked sunday not sure', 'specifics' => ['sunday']],
            ],
            'context' => array_merge($context, ['topic' => $topic, 'ambiguity' => 'sunday_rest_day']),
        ];
    }

    if (preg_match('/sunday.*(rest|rest day)/i', $lower) || preg_match('/rest day.*sunday/i', $lower)) {
        return [
            'message' => 'Got it. Was Sunday the employee\'s scheduled rest day, or was it a regular working day that happened to fall on a Sunday?',
            'options' => [
                ['label' => 'Yes, it was their rest day', 'value' => 'employee worked on rest day sunday', 'specifics' => ['rest day', 'sunday']],
                ['label' => 'No, it was a regular workday', 'value' => 'employee worked sunday regular workday', 'specifics' => ['regular workday', 'sunday']],
                ['label' => 'I\'m not sure', 'value' => 'employee worked sunday not sure', 'specifics' => ['sunday']],
            ],
            'context' => array_merge($context, ['topic' => 'working_hours', 'ambiguity' => 'sunday_rest_day']),
        ];
    }

    if (preg_match('/holiday.*(concern|problem|issue|question)/i', $lower) || $lower === 'holiday concern') {
        return [
            'message' => 'Sure. What type of holiday concern is this?',
            'options' => [
                ['label' => 'Holiday Pay', 'value' => 'holiday pay', 'specifics' => ['holiday pay']],
                ['label' => 'Working on a Holiday', 'value' => 'working on a holiday', 'specifics' => ['working on holiday']],
                ['label' => 'Something Else', 'value' => '__other__', 'specifics' => []],
            ],
            'context' => array_merge($context, ['topic' => 'working_hours']),
        ];
    }

    if (preg_match('/^employee\s+(worked|work)\s+(sunday|saturday)/i', $lower)) {
        $day = preg_replace('/^employee\s+(worked|work)\s+/i', '', $lower);
        $day = trim($day);
        return [
            'message' => "Got it. Was {$day} the employee's scheduled rest day?",
            'options' => [
                ['label' => 'Yes, it was their rest day', 'value' => 'employee worked on ' . $day . ' rest day', 'specifics' => ['rest day', $day]],
                ['label' => 'No, it was a regular workday', 'value' => 'employee worked ' . $day . ' regular workday', 'specifics' => ['regular workday', $day]],
                ['label' => 'I\'m not sure', 'value' => 'employee worked ' . $day . ' not sure', 'specifics' => [$day]],
            ],
            'context' => array_merge($context, ['topic' => 'working_hours', 'ambiguity' => $day . '_rest_day']),
        ];
    }

    return null;
}

function detectTopic(string $query): string {
    $lower = strtolower($query);
    if (preg_match('/wage|salary|pay|compensation|minimum wage|overtime|deduction|unpaid|rest day|holiday pay|night differential|13th month|thirteenth month|service incentive/', $lower)) return 'salary';
    if (preg_match('/leave|maternity|paternity|solo parent|parental|vacation|sick|holiday/', $lower)) return 'leave';
    if (preg_match('/safety|injury|accident|ppe|hazard|workplace safety|occupational/', $lower)) return 'safety';
    if (preg_match('/harassment|discrimination|safe spaces|gender|violence|sexual/', $lower)) return 'harassment';
    if (preg_match('/termination|separation|dismissal|retirement|resignation|constructive/', $lower)) return 'termination';
    if (preg_match('/contribution|sss|philhealth|pagibig|government contribution/', $lower)) return 'contributions';
    if (preg_match('/foreign|alien|permit|employment of foreign/', $lower)) return 'foreign';
    if (preg_match('/contract|agreement|employment contract/', $lower)) return 'contract';
    if (preg_match('/document|requirement|employee document/', $lower)) return 'documents';
    if (preg_match('/union|labor relations|collective bargaining|certification/', $lower)) return 'labor_relations';
    if (preg_match('/privacy|data privacy|personal data/', $lower)) return 'privacy';
    if (preg_match('/mental health|wellness/', $lower)) return 'mental_health';
    if (preg_match('/benefit/', $lower)) return 'benefits';
    if (preg_match('/hour|shift|night shift|working hours|rest day/', $lower)) return 'working_hours';
    if (str_contains($lower, 'workplace concern')) return 'workplace';
    return 'general';
}

function getQuickRepliesForTopic(string $topic): array {
    switch ($topic) {
        case 'salary':
            return [
                ['label' => 'Minimum Wage', 'value' => 'minimum wage'],
                ['label' => 'Overtime Pay', 'value' => 'overtime pay'],
                ['label' => 'Holiday Pay', 'value' => 'holiday pay'],
                ['label' => 'Rest Day Pay', 'value' => 'rest day pay'],
                ['label' => 'Salary Deductions', 'value' => 'salary deductions'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
        case 'leave':
            return [
                ['label' => 'Maternity Leave', 'value' => 'maternity leave'],
                ['label' => 'Paternity Leave', 'value' => 'paternity leave'],
                ['label' => 'Solo Parent Leave', 'value' => 'solo parent leave'],
                ['label' => 'Vacation Leave', 'value' => 'vacation leave'],
                ['label' => 'Sick Leave', 'value' => 'sick leave'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
        case 'safety':
            return [
                ['label' => 'Workplace Injury', 'value' => 'workplace injury'],
                ['label' => 'Lack of PPE', 'value' => 'lack of PPE'],
                ['label' => 'Hazard Identification', 'value' => 'hazard identification'],
                ['label' => 'Safety Violations', 'value' => 'safety violations'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
        case 'harassment':
            return [
                ['label' => 'Sexual Harassment', 'value' => 'sexual harassment'],
                ['label' => 'Gender-Based Harassment', 'value' => 'gender-based harassment'],
                ['label' => 'Discrimination', 'value' => 'discrimination'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
        case 'termination':
            return [
                ['label' => 'Employee Termination', 'value' => 'employee termination'],
                ['label' => 'Separation Pay', 'value' => 'separation pay'],
                ['label' => 'Retirement', 'value' => 'retirement'],
                ['label' => 'Regularization', 'value' => 'regularization'],
                ['label' => 'Due Process', 'value' => 'due process'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
        case 'contributions':
            return [
                ['label' => 'SSS', 'value' => 'SSS concern'],
                ['label' => 'PhilHealth', 'value' => 'PhilHealth concern'],
                ['label' => 'Pag-IBIG', 'value' => 'Pag-IBIG concern'],
                ['label' => 'BIR', 'value' => 'BIR concern'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
        case 'benefits':
            return [
                ['label' => 'SSS', 'value' => 'SSS benefits'],
                ['label' => 'PhilHealth', 'value' => 'PhilHealth benefits'],
                ['label' => 'Pag-IBIG', 'value' => 'Pag-IBIG benefits'],
                ['label' => 'BIR', 'value' => 'BIR benefits'],
                ['label' => 'Maternity', 'value' => 'maternity benefits'],
                ['label' => 'Paternity', 'value' => 'paternity benefits'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
        case 'working_hours':
            return [
                ['label' => 'Overtime', 'value' => 'overtime hours'],
                ['label' => 'Rest Day', 'value' => 'rest day work'],
                ['label' => 'Holiday Work', 'value' => 'holiday work'],
                ['label' => 'Night Shift', 'value' => 'night shift'],
                ['label' => 'Working Hours', 'value' => 'working hours'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
        case 'general':
        default:
            return [
                ['label' => 'Wages & Salary', 'value' => 'salary concern'],
                ['label' => 'Benefits', 'value' => 'benefits concern'],
                ['label' => 'Leave', 'value' => 'leave concern'],
                ['label' => 'Working Hours', 'value' => 'working hours concern'],
                ['label' => 'Government Contributions', 'value' => 'government contributions concern'],
                ['label' => 'Workplace Concern', 'value' => 'workplace concern'],
                ['label' => 'Other', 'value' => '__other__'],
            ];
    }
}

function getClarificationMessage(string $topic): string {
    switch ($topic) {
        case 'salary':
            return "Got it! What part of the salary concern are you checking?";
        case 'leave':
            return "Sure! Which leave are you asking about?";
        case 'safety':
            return "I can help with that. What is the issue mainly about? For example: workplace injury, lack of PPE, hazard identification, or safety violations.";
        case 'harassment':
            return "Sure. Is the concern about sexual harassment, gender-based harassment, discrimination, or another workplace conduct issue?";
        case 'termination':
            return "Got it. Is the concern about employee termination, separation pay, retirement, regularization, or due process?";
        case 'contributions':
            return "Sure. Is the concern about SSS, PhilHealth, Pag-IBIG, or other government contribution compliance?";
        case 'benefits':
            return "Sure! Which benefit are you asking about?";
        case 'working_hours':
            return "Got it. What are you trying to check?";
        case 'foreign':
            return "I can help narrow that down. Is the concern about Alien Employment Permits, labor market testing, or work permit compliance for foreign nationals?";
        case 'contract':
            return "Sure. Is the concern about employment contracts, contract renewal, termination clauses, or another contractual matter?";
        case 'documents':
            return "Got it. Is the concern about employee document requirements, onboarding documents, or compliance documentation?";
        case 'labor_relations':
            return "Sure. Is the concern about unions, collective bargaining, certification elections, or labor disputes?";
        case 'privacy':
            return "Got it. Is the concern about employee data protection, data breach, consent, or privacy compliance?";
        case 'mental_health':
            return "Sure. Is the concern about workplace mental health programs, employee support, or mental health-related accommodations?";
        default:
            return "I can help with that. Could you tell me a little more about the concern? For example: salary or minimum wage, leave or employee benefits, workplace safety, harassment or discrimination, employee documents, termination or separation, government contributions, or working hours.";
    }
}

function generateMatchedConcepts(string $query, array $reference): string {
    $words = preg_split('/\s+/', strtolower($query), -1, PREG_SPLIT_NO_EMPTY);
    if (empty($words)) {
        return 'Your concern mentions terms that appear in this reference.';
    }

    $haystack = strtolower(($reference['keywords'] ?? '') . ' ' . ($reference['title'] ?? '') . ' ' . ($reference['summary'] ?? '') . ' ' . ($reference['short_title'] ?? ''));
    $matched = [];
    foreach ($words as $word) {
        if (strlen($word) < 3) continue;
        if (str_contains($haystack, $word)) {
            $matched[] = $word;
        }
    }
    $matched = array_values(array_unique($matched));
    if (empty($matched)) {
        return 'Your concern mentions terms that appear in this reference.';
    }
    $examples = array_slice($matched, 0, 4);
    return 'Your concern mentions ' . implode(', ', $examples) . ', which are concepts associated with this reference.';
}

function evaluateMatchConfidence(string $query, array $result): string
{
    $lower = strtolower($query);
    $keywords = strtolower($result['keywords'] ?? '');
    $title = strtolower($result['title'] ?? '');
    $shortTitle = strtolower($result['short_title'] ?? '');
    $summary = strtolower($result['summary'] ?? '');
    $description = strtolower($result['description'] ?? '');
    $relatedLaw = strtolower($result['related_law'] ?? '');
    $categoryName = strtolower($result['category_name'] ?? '');

    $combined = $keywords . ' ' . $title . ' ' . $shortTitle . ' ' . $summary . ' ' . $description . ' ' . $relatedLaw;

    $words = preg_split('/\s+/', $lower, -1, PREG_SPLIT_NO_EMPTY);
    if (empty($words)) {
        return 'low';
    }

    $matchedCount = 0;
    foreach ($words as $word) {
        if (strlen($word) < 3) continue;
        if (str_contains($combined, $word)) {
            $matchedCount++;
        }
    }

    $totalWords = count(array_filter($words, function ($w) { return strlen($w) >= 3; }));
    if ($totalWords === 0) {
        return 'low';
    }

    $matchRatio = $matchedCount / $totalWords;

    $topic = detectTopic($query);
    $categoryMatch = false;
    if ($topic !== 'general' && $categoryName !== '') {
        $topicCategoryMap = [
            'salary' => ['labor laws', 'wage orders', 'labor advisories'],
            'leave' => ['labor laws', 'labor advisories'],
            'safety' => ['labor laws', 'department orders'],
            'harassment' => ['labor laws', 'department orders'],
            'termination' => ['labor laws'],
            'contributions' => ['labor laws', 'labor advisories'],
            'foreign' => ['department orders', 'labor laws'],
            'contract' => ['labor laws'],
            'documents' => ['department orders', 'memorandum'],
            'labor_relations' => ['labor laws', 'department orders'],
            'privacy' => ['labor laws'],
            'mental_health' => ['labor laws', 'department orders'],
            'benefits' => ['labor laws', 'labor advisories'],
            'working_hours' => ['labor laws', 'wage orders', 'department orders'],
        ];

        $expectedCategories = $topicCategoryMap[$topic] ?? [];
        foreach ($expectedCategories as $ec) {
            if (str_contains($categoryName, $ec)) {
                $categoryMatch = true;
                break;
            }
        }
    }

    if ($matchRatio >= 0.5 && $matchedCount >= 3 && !empty($result['relevance']) && $result['relevance'] >= 10) {
        return 'high';
    }

    if ($categoryMatch && $matchRatio >= 0.4 && $matchedCount >= 2 && !empty($result['relevance']) && $result['relevance'] >= 8) {
        return 'high';
    }

    if ($matchedCount >= 2 || (!empty($result['relevance']) && $result['relevance'] >= 5)) {
        return 'partial';
    }

    return 'low';
}

function isFollowUpClosing(string $query): bool {
    $lower = strtolower($query);
    $closing = [
        'no that is all', 'no that\'s all', 'no more', 'no thank you', 'no thanks',
        'none', 'nothing else', 'that\'s it', 'that is it', 'all good', 'all set',
        'no further', 'no more questions', 'no question', 'no need', 'no thanks',
    ];
    foreach ($closing as $c) {
        if ($lower === $c || str_contains($lower, $c)) {
            return true;
        }
    }
    return false;
}

function isFollowUpContinue(string $query): bool {
    $lower = strtolower($query);
    $continue = [
        'yes another question', 'yes i have another question', 'yes one more',
        'yes more', 'yes please', 'yes sure', 'another', 'more question',
        'another question', 'one more question', 'yes thanks', 'yes thank you',
    ];
    foreach ($continue as $c) {
        if ($lower === $c || str_contains($lower, $c)) {
            return true;
        }
    }
    return false;
}

function isIdleCheckYes(string $query): bool {
    $lower = strtolower($query);
    $yes = [
        'yes', 'yes still here', 'yes i\'m still here', 'yes im still here',
        'yes here', 'still here', 'i\'m still here', 'im still here',
        'yes please', 'yes sure', 'yes continue', 'continue',
    ];
    foreach ($yes as $y) {
        if ($lower === $y || str_contains($lower, $y)) {
            return true;
        }
    }
    return false;
}

function isIdleCheckNo(string $query): bool {
    $lower = strtolower($query);
    $no = [
        'no', 'no that\'s all', 'no that is all', 'no more', 'no thank you',
        'no thanks', 'none', 'nothing else', 'that\'s it', 'that is it',
        'all good', 'all set', 'no further', 'no more questions',
        'no question', 'no need', 'close', 'end', 'bye',
    ];
    foreach ($no as $n) {
        if ($lower === $n || str_contains($lower, $n)) {
            return true;
        }
    }
    return false;
}

function isDeveloperQuery(string $query): bool {
    $lower = strtolower($query);

    $baseKeywords = [
        'developer', 'creator', 'created by', 'made by', 'who made', 'who created',
        'programmer', 'scrum master', 'document specialist', 'business analyst',
        'maria cheska jalotjot', 'phil vincent lope', 'marycris eliaga', 'johna mhae azucena',
        'team', 'members', 'author', 'built by', 'developed by', 'lala ai team',
        'dev', 'devs', 'coder', 'engineer', 'architect', 'designer',
        'who built', 'who coded', 'who designed', 'who programmed',
        'your team', 'your creators', 'your developers', 'your programmers',
        'who is your developer', 'who is ur developer', 'whoz ur developer',
        'who is the developer', 'who is the creator',
    ];

    $typoTolerant = [
        'devloper', 'devoloper', 'develloper', 'devlper', 'devolepr',
        'creater', 'creatr', 'creater', 'prgrammer', 'programer', 'progammer',
        'scrrum', 'scruum', 'doc specialist', 'bussiness analyst', 'bussines analyst',
        'jalotjot', 'lope phil', 'eliaga mary', 'azucena johna',
        'tean', 'teem', 'memebrs', 'auther', 'builtin',
    ];

    $combined = array_merge($baseKeywords, $typoTolerant);
    foreach ($combined as $keyword) {
        if (str_contains($lower, $keyword)) {
            return true;
        }
    }

    $developerPatterns = [
        '/who\s+is\s+your\s+(dev|lala|system|app|module)/i',
        '/who\s+(dev|build|made|creat)/i',
        '/your\s+(dev|developer|creator|programmer)/i',
        '/tell\s+me\s+about\s+(dev|developer|creator|programmer|team)/i',
        '/who\s+(are|r)\s+(u|you)\s+(made|build|created|developed)/i',
    ];

    foreach ($developerPatterns as $pattern) {
        if (preg_match($pattern, $lower)) {
            return true;
        }
    }

    return false;
}

function getDeveloperInfo(): string {
    $nl = "\n";
    $html = '<div class="ll-developer-info">';
    $html .= '<div class="ll-developer-header">Development Team</div>';
    $html .= '<div class="ll-developer-list">';

    $members = [
        [
            'role' => 'Programmer',
            'name' => 'MARIA CHESKA JALOTJOT',
            'email' => 'jalotjot.cheska@icloud.com',
            'desc' => 'Develops and maintains the Legal and Compliance Management Module, implements system functionalities, and performs coding, testing, and debugging.',
        ],
        [
            'role' => 'Scrum Master',
            'name' => 'PHIL VINCENT LOPE',
            'email' => 'lope.philvince1105@gmail.com',
            'desc' => 'Facilitates team coordination, monitors sprint progress, and serves as a bridge between stakeholders and the development team.',
        ],
        [
            'role' => 'Document Specialist',
            'name' => 'MARYCRIS ELIAGA',
            'email' => 'eliagamarycris@gmail.com',
            'desc' => 'Analyzes system requirements, evaluates existing HR legal and compliance processes, and ensures that the system design supports organizational needs.',
        ],
        [
            'role' => 'Business Analyst',
            'name' => 'JOHNA MHAE AZUCENA',
            'email' => 'johnaazucena@gmail.com',
            'desc' => 'Identifies business requirements, coordinates with stakeholders, and ensures that the module aligns with the Human Resource Department\'s legal and compliance management needs.',
        ],
    ];

    foreach ($members as $m) {
        $html .= '<div class="ll-developer-card">';
        $html .= '<div class="ll-developer-card-role">' . htmlspecialchars($m['role']) . '</div>';
        $html .= '<div class="ll-developer-card-name">' . htmlspecialchars($m['name']) . '</div>';
        $html .= '<div class="ll-developer-card-contact"><a href="mailto:' . htmlspecialchars($m['email']) . '">' . htmlspecialchars($m['email']) . '</a></div>';
        $html .= '<div class="ll-developer-card-desc">' . htmlspecialchars($m['desc']) . '</div>';
        $html .= '</div>';
    }

    $html .= '</div></div>';
    return $html;
}
