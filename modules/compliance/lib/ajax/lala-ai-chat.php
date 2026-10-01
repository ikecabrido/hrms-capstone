<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../classes/LaborLawReference.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

/*
|--------------------------------------------------------------------------
| OPTIONS / CORS
|--------------------------------------------------------------------------
*/

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Method not allowed.'
    ], 405);
}

/*
|--------------------------------------------------------------------------
| READ REQUEST
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents('php://input');

$input = json_decode(
    $rawInput ?: '{}',
    true
);

if (!is_array($input)) {
    $input = [];
}

$rawQuery = trim((string)($input['query'] ?? ''));

$clientContext = (
    isset($input['context']) &&
    is_array($input['context'])
)
    ? $input['context']
    : [];

/*
|--------------------------------------------------------------------------
| MAIN
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Empty Query
    |--------------------------------------------------------------------------
    */

    if ($rawQuery === '') {

        jsonResponse([
            'success' => true,
            'type' => 'greeting',
            'message' => 'Please describe the HR or workplace concern you want to search for.',
            'options' => [
                [
                    'label' => 'Labor Law',
                    'value' => 'labor law reference'
                ],
                [
                    'label' => 'Wages & Salary',
                    'value' => 'minimum wage concern'
                ],
                [
                    'label' => 'Leave',
                    'value' => 'leave concern'
                ],
                [
                    'label' => 'Working Hours',
                    'value' => 'working hours concern'
                ],
                [
                    'label' => 'Government Contributions',
                    'value' => 'government contributions concern'
                ],
                [
                    'label' => 'Workplace Concern',
                    'value' => 'workplace concern'
                ]
            ],
            'context' => resetContext()
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE
    |--------------------------------------------------------------------------
    */

    $database = new Database();

    if (
        method_exists($database, 'hasConnectionError') &&
        $database->hasConnectionError()
    ) {
        $dbError = method_exists($database, 'getConnectionError')
            ? $database->getConnectionError()
            : 'Unknown database connection error';

        error_log(
            'Lala AI database connection error: ' . $dbError
        );

        jsonResponse([
            'success' => false,
            'message' => 'I could not connect to the labor-law reference database right now. Please try again in a moment.'
        ], 500);

        exit;
    }

    $db = $database->getConnection();

    if (!$db instanceof PDO) {

        error_log(
            'Lala AI database error: Database::getConnection() did not return a PDO connection.'
        );

        jsonResponse([
            'success' => false,
            'message' => 'I could not connect to the labor-law reference database right now. Please try again in a moment.'
        ], 500);

        exit;
    }

    $model = new LaborLawReference($db);

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE QUERY
    |--------------------------------------------------------------------------
    */

    $query = normalizeQuery($rawQuery);
    $lower = strtolower($query);

    /*
    |--------------------------------------------------------------------------
    | GREETING
    |--------------------------------------------------------------------------
    */

    if (isGreeting($query)) {

        $employeeName = trim(
            (string)($_SESSION['employee_name'] ?? 'there')
        );

        $firstName = trim(
            explode(' ', $employeeName)[0]
        );

        if ($firstName === '') {
            $firstName = 'there';
        }

        jsonResponse([
            'success' => true,
            'type' => 'greeting',
            'message' =>
                'Hi, ' . $firstName . '! 👋' .
                "\n\n" .
                "I'm Lala AI, your Labor Law Reference Assistant." .
                "\n\n" .
                "Tell me what's happening at work, and I'll help you find the most relevant labor-law references." .
                "\n\n" .
                "You can simply describe the situation in your own words.",
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext()
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | UNRELATED QUESTIONS
    |--------------------------------------------------------------------------
    */

    if (isUnrelated($query)) {

        jsonResponse([
            'success' => true,
            'type' => 'unrelated',
            'message' =>
                "I'm designed to help with HR, workplace, and labor-law reference concerns. " .
                "Try describing an employee or workplace situation instead." .
                "\n\n" .
                'For example: "An employee was injured at work and was not provided proper PPE."',
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext()
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIGUITY
    |--------------------------------------------------------------------------
    */

    $ambiguity = detectAmbiguity(
        $query,
        $clientContext
    );

    if ($ambiguity !== null) {

        jsonResponse([
            'success' => true,
            'type' => 'clarify',
            'message' => $ambiguity['message'],
            'options' => $ambiguity['options'],
            'context' => $ambiguity['context'] ?? $clientContext
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | DEVELOPER INFORMATION
    |--------------------------------------------------------------------------
    */

    if (isDeveloperQuery($query)) {

        jsonResponse([
            'success' => true,
            'type' => 'developer',
            'message' => getDeveloperInfo(),
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext()
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | VAGUE QUESTION
    |--------------------------------------------------------------------------
    */

    if (isVague($query)) {

        $topic = detectTopic($query);

        jsonResponse([
            'success' => true,
            'type' => 'clarify',
            'message' => getClarificationMessage($topic),
            'options' => getQuickRepliesForTopic($topic),
            'context' => array_merge(
                $clientContext,
                [
                    'topic' => $topic
                ]
            )
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FOLLOW-UP CLOSING
    |--------------------------------------------------------------------------
    */

    if (isFollowUpClosing($query)) {

        jsonResponse([
            'success' => true,
            'type' => 'goodbye',
            'message' => 'Thank you and have a great shift! 👍',
            'options' => [],
            'context' => resetContext()
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FOLLOW-UP CONTINUE
    |--------------------------------------------------------------------------
    */

    if (isFollowUpContinue($query)) {

        jsonResponse([
            'success' => true,
            'type' => 'thanks',
            'message' =>
                "Of course! Feel free to describe another concern and I'll help you find the relevant labor-law references.",
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext()
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | IDLE CHECK
    |--------------------------------------------------------------------------
    */

    if (isIdleCheckNo($query)) {

        jsonResponse([
            'success' => true,
            'type' => 'goodbye',
            'message' => 'Thank you and have a great shift! 👍',
            'options' => [],
            'context' => resetContext()
        ]);

        exit;
    }

    if (isIdleCheckYes($query)) {

        jsonResponse([
            'success' => true,
            'type' => 'thanks',
            'message' =>
                "Great! Feel free to describe another concern and I'll help you find the relevant labor-law references.",
            'options' => getQuickRepliesForTopic('general'),
            'context' => resetContext()
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | DETECT TOPIC
    |--------------------------------------------------------------------------
    */

    $topic = detectTopic($query);

    /*
    |--------------------------------------------------------------------------
    | BUILD EFFECTIVE QUERY
    |--------------------------------------------------------------------------
    */

    $effectiveQuery = buildEffectiveQuery(
        $query,
        $clientContext
    );

    /*
    |--------------------------------------------------------------------------
    | DATABASE SEARCH
    |--------------------------------------------------------------------------
    */

    $results = $model->searchReferencesForAssistant(
        $effectiveQuery,
        10
    );

    $confidenceQuery = $query;

    /*
    |--------------------------------------------------------------------------
    | WORKPLACE FALLBACK SEARCH
    |--------------------------------------------------------------------------
    */

    if (
        $topic === 'workplace' &&
        empty($results)
    ) {

        $workplaceKeywords = [
            'harassment',
            'discrimination',
            'safety',
            'occupational'
        ];

        $allResults = [];
        $seenIds = [];

        foreach ($workplaceKeywords as $keyword) {

            $keywordQuery = buildEffectiveQuery(
                $keyword,
                $clientContext
            );

            $keywordResults =
                $model->searchReferencesForAssistant(
                    $keywordQuery,
                    10
                );

            foreach ($keywordResults as $result) {

                $id = $result['id'] ?? null;

                if ($id === null) {
                    continue;
                }

                if (!isset($seenIds[$id])) {

                    $allResults[] = $result;
                    $seenIds[$id] = true;
                }
            }
        }

        $results = array_slice(
            $allResults,
            0,
            10
        );

        $confidenceQuery =
            implode(' ', $workplaceKeywords);
    }

    /*
    |--------------------------------------------------------------------------
    | WORKING HOURS FALLBACK SEARCH
    |--------------------------------------------------------------------------
    */

    if (
        $topic === 'working_hours' &&
        empty($results)
    ) {

        $workingHoursKeywords = [
            'overtime',
            'rest day',
            'holiday work',
            'night shift',
            'working hours',
            'hours of work'
        ];

        $allResults = [];
        $seenIds = [];

        foreach ($workingHoursKeywords as $keyword) {

            $keywordQuery = buildEffectiveQuery(
                $keyword,
                $clientContext
            );

            $keywordResults =
                $model->searchReferencesForAssistant(
                    $keywordQuery,
                    10
                );

            foreach ($keywordResults as $result) {

                $id = $result['id'] ?? null;

                if ($id === null) {
                    continue;
                }

                if (!isset($seenIds[$id])) {

                    $allResults[] = $result;
                    $seenIds[$id] = true;
                }
            }
        }

        $results = array_slice(
            $allResults,
            0,
            10
        );

        $confidenceQuery =
            implode(' ', $workingHoursKeywords);
    }

    /*
    |--------------------------------------------------------------------------
    | NO RESULTS
    |--------------------------------------------------------------------------
    */

    if (empty($results)) {

        $fallback =
            LaborLawReference::getAgencyForQuery($query);

        jsonResponse([
            'success' => true,
            'type' => 'fallback',
            'message' =>
                "I couldn't find a sufficiently specific answer in the LALA knowledge database for your concern.",
            'fallback_agency' => $fallback['agency'] ?? '',
            'fallback_url' => $fallback['url'] ?? '',
            'fallback_why' => $fallback['why'] ?? '',
            'context' => array_merge(
                $clientContext,
                [
                    'topic' => $topic
                ]
            )
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIDENCE GROUPING
    |--------------------------------------------------------------------------
    */

    $highMatches = [];
    $partialMatches = [];
    $lowMatches = [];

    foreach ($results as $result) {

        $confidence =
            evaluateMatchConfidence(
                $confidenceQuery,
                $result
            );

        if ($confidence === 'high') {

            $highMatches[] = $result;

        } elseif ($confidence === 'partial') {

            $partialMatches[] = $result;

        } else {

            $lowMatches[] = $result;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REMOVE OUTDATED HIGH MATCHES
    |--------------------------------------------------------------------------
    */

    $validHighMatches = [];

    foreach ($highMatches as $result) {

        try {

            if (!$model->isLikelyOutdated($result)) {
                $validHighMatches[] = $result;
            }

        } catch (Throwable $e) {

            error_log(
                'Lala AI outdated-check error: ' .
                $e->getMessage()
            );

            /*
             * If the outdated check itself fails,
             * retain the result rather than crashing
             * the whole assistant.
             */
            $validHighMatches[] = $result;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT INFORMATION CHECK
    |--------------------------------------------------------------------------
    */

    $needsCurrentInfo = (bool)preg_match(
        '/minimum wage|wage order|contribution rate|current rate|current amount|latest|2024|2025|2026/i',
        $lower
    );

    if (!empty($validHighMatches)) {

        if ($needsCurrentInfo) {

            $allRecent = true;

            foreach ($validHighMatches as $result) {

                if (!empty($result['date_issued'])) {

                    $timestamp =
                        strtotime(
                            (string)$result['date_issued']
                        );

                    if ($timestamp !== false) {

                        $year =
                            (int)date(
                                'Y',
                                $timestamp
                            );

                        if ($year < 2024) {

                            $allRecent = false;
                            break;
                        }
                    }
                }
            }

            if (!$allRecent) {

                $fallback =
                    LaborLawReference::getAgencyForQuery(
                        $query
                    );

                jsonResponse([
                    'success' => true,
                    'type' => 'fallback',
                    'message' =>
                        "I couldn't find a sufficiently specific and current answer in the LALA knowledge database for your concern.",
                    'fallback_agency' => $fallback['agency'] ?? '',
                    'fallback_url' => $fallback['url'] ?? '',
                    'fallback_why' =>
                        ($fallback['why'] ?? '') .
                        ' This information may change frequently and should be verified with the official source.',
                    'context' => array_merge(
                        $clientContext,
                        [
                            'topic' => $topic
                        ]
                    )
                ]);

                exit;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FORMAT HIGH RESULTS
        |--------------------------------------------------------------------------
        */

        $formatted = [];

        foreach ($validHighMatches as $result) {

            $formatted[] = formatReference(
                $effectiveQuery,
                $result
            );
        }

        jsonResponse([
            'success' => true,
            'type' => 'results',
            'message' =>
                'I found references that may relate to your concern:',
            'results' => $formatted,
            'context' => array_merge(
                $clientContext,
                [
                    'topic' => $topic,
                    'last_query' => $query
                ]
            ),
            'follow_up' => true,
            'follow_up_message' =>
                'Is there anything else I can help you with?',
            'follow_up_options' => [
                [
                    'label' => 'Yes, I have another question',
                    'value' => 'yes another question'
                ],
                [
                    'label' => "No, that's all",
                    'value' => 'no that is all'
                ]
            ]
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | PARTIAL MATCHES
    |--------------------------------------------------------------------------
    */

    if (!empty($partialMatches)) {

        $formatted = [];

        foreach ($partialMatches as $result) {

            $formatted[] = formatReference(
                $effectiveQuery,
                $result
            );
        }

        $fallback =
            LaborLawReference::getAgencyForQuery(
                $query
            );

        jsonResponse([
            'success' => true,
            'type' => 'partial_match',
            'message' =>
                "I found some related references, but they may not fully address your specific question. Here's what I found in the LALA knowledge database:",
            'results' => $formatted,
            'fallback_agency' => $fallback['agency'] ?? '',
            'fallback_url' => $fallback['url'] ?? '',
            'fallback_why' => $fallback['why'] ?? '',
            'context' => array_merge(
                $clientContext,
                [
                    'topic' => $topic,
                    'last_query' => $query
                ]
            ),
            'follow_up' => true,
            'follow_up_message' =>
                'Is there anything else I can help you with?',
            'follow_up_options' => [
                [
                    'label' => 'Yes, I have another question',
                    'value' => 'yes another question'
                ],
                [
                    'label' => "No, that's all",
                    'value' => 'no that is all'
                ]
            ]
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FINAL FALLBACK
    |--------------------------------------------------------------------------
    */

    $fallback =
        LaborLawReference::getAgencyForQuery($query);

    jsonResponse([
        'success' => true,
        'type' => 'fallback',
        'message' =>
            "I couldn't find a sufficiently specific answer in the LALA knowledge database for your concern.",
        'fallback_agency' => $fallback['agency'] ?? '',
        'fallback_url' => $fallback['url'] ?? '',
        'fallback_why' => $fallback['why'] ?? '',
        'context' => array_merge(
            $clientContext,
            [
                'topic' => $topic
            ]
        )
    ]);

    exit;

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT ERROR LOG
    |--------------------------------------------------------------------------
    |
    | Throwable catches both:
    | - Exception
    | - Error
    |
    | This is important because a PHP Error was previously able
    | to bypass catch (Exception).
    |
    */

    error_log(
        'Labor Law Assistant error: ' .
        $e->getMessage() .
        ' | File: ' .
        $e->getFile() .
        ' | Line: ' .
        $e->getLine()
    );

    jsonResponse([
        'success' => false,
        'message' =>
            "I couldn't check the references right now. Please try again in a moment."
    ], 500);

    exit;
}

/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function jsonResponse(
    array $data,
    int $statusCode = 200
): void {

    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| CONTEXT
|--------------------------------------------------------------------------
*/

function resetContext(): array
{
    return [
        'topic' => null,
        'last_query' => null,
        'specifics' => []
    ];
}

/*
|--------------------------------------------------------------------------
| NORMALIZE QUERY
|--------------------------------------------------------------------------
*/

function normalizeQuery(string $query): string
{
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
        'employeer' => 'employer',
        'employess' => 'employees',
        'workd' => 'worked',
        'workded' => 'worked',
        'res day' => 'rest day',
        'restday' => 'rest day',
        'rest days' => 'rest day',
        'sundae' => 'sunday',
        'sundey' => 'sunday',
        'sunduy' => 'sunday'
    ];

    $q = strtolower(trim($query));

    foreach ($map as $bad => $good) {
        $q = str_replace($bad, $good, $q);
    }

    $q = preg_replace('/\s+/', ' ', $q);
    $q = trim((string)$q);

    return $q;
}

/*
|--------------------------------------------------------------------------
| GREETING
|--------------------------------------------------------------------------
*/

function isGreeting(string $query): bool
{
    $lower = strtolower(trim($query));

    $greetings = [
        'hi',
        'hello',
        'hey',
        'good morning',
        'good afternoon',
        'good evening',
        'greetings',
        'howdy'
    ];

    if (in_array($lower, $greetings, true)) {
        return true;
    }

    if (
        preg_match(
            '/^(hi|hello|hey|good\s+(morning|afternoon|evening))\b/i',
            $lower
        ) &&
        str_word_count($lower) <= 4
    ) {
        return true;
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| BUILD EFFECTIVE QUERY
|--------------------------------------------------------------------------
*/

function buildEffectiveQuery(
    string $query,
    array $context
): string {

    $parts = [$query];

    if (
        !empty($context['topic']) &&
        !str_contains(
            strtolower($query),
            strtolower((string)$context['topic'])
        )
    ) {
        $parts[] = (string)$context['topic'];
    }

    if (
        !empty($context['specifics']) &&
        is_array($context['specifics'])
    ) {

        foreach ($context['specifics'] as $specific) {

            $specific = trim((string)$specific);

            if ($specific === '') {
                continue;
            }

            if (
                !str_contains(
                    strtolower($query),
                    strtolower($specific)
                )
            ) {
                $parts[] = $specific;
            }
        }
    }

    $parts = array_unique(
        array_filter(
            array_map('trim', $parts)
        )
    );

    return implode(' ', $parts);
}

/*
|--------------------------------------------------------------------------
| UNRELATED
|--------------------------------------------------------------------------
*/

function isUnrelated(string $query): bool
{
    $lower = strtolower($query);

    $unrelated = [
        'weather',
        'stock',
        'market',
        'sports',
        'news',
        'movie',
        'game',
        'temperature',
        'crypto',
        'bitcoin',
        'food',
        'recipe',
        'travel',
        'music',
        'song',
        'joke',
        'funny',
        'president',
        'election',
        'politics'
    ];

    foreach ($unrelated as $word) {

        if (str_contains($lower, $word)) {
            return true;
        }
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| VAGUE
|--------------------------------------------------------------------------
*/

function isVague(string $query): bool
{
    $lower = strtolower(trim($query));

    if ($lower === '') {
        return true;
    }

    $vaguePhrases = [
        'i have a problem',
        'my employee has an issue',
        'i need help',
        'labor law question',
        'there is a problem at work',
        'employee concern',
        'what should i do',
        'i have a question',
        'help me',
        'something happened',
        'there is an issue',
        'i have an issue',
        'employee problem',
        'can you help',
        'i want to ask',
        'help me with this',
        'i have an employee problem',
        'employee issue',
        'workplace issue',
        'hr concern',
        'compliance issue'
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
        'wage',
        'salary',
        'pay',
        'compensation',
        'minimum wage',
        'overtime',
        'leave',
        'maternity',
        'paternity',
        'solo parent',
        'parental',
        'vacation',
        'safety',
        'injury',
        'accident',
        'ppe',
        'hazard',
        'workplace safety',
        'harassment',
        'discrimination',
        'safe spaces',
        'gender',
        'violence',
        'sexual',
        'termination',
        'separation',
        'dismissal',
        'retirement',
        'resignation',
        'constructive',
        'contribution',
        'sss',
        'philhealth',
        'pagibig',
        'pag-ibig',
        'bir',
        'government contribution',
        'foreign',
        'alien',
        'permit',
        'contract',
        'document',
        'requirement',
        'union',
        'labor relations',
        'collective bargaining',
        'certification',
        'privacy',
        'data privacy',
        'mental health',
        'wellness',
        'employee',
        'employer',
        'work',
        'hours',
        'night shift',
        'holiday pay',
        '13th month',
        'thirteenth month',
        'service incentive',
        'retirement pay',
        'constructive dismissal',
        'security of tenure',
        'probationary',
        'regularization',
        'rest day',
        'holiday',
        'benefit',
        'bonus',
        'night differential',
        'sunday',
        'saturday',
        'rest day work',
        'working hours',
        'workplace concern',
        'workplace'
    ];

    $hasSpecific = false;

    foreach ($specificWords as $word) {

        if (str_contains($lower, $word)) {

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
        'labor law reference'
    ];

    if (in_array($lower, $generalTopicValues, true)) {
        return true;
    }

    if ($wordCount < 2) {

        foreach ($specificWords as $word) {

            if ($lower === $word) {
                return false;
            }
        }

        return true;
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| AMBIGUITY
|--------------------------------------------------------------------------
*/

function detectAmbiguity(
    string $query,
    array $context
): ?array {

    $lower = strtolower($query);

    /*
    |--------------------------------------------------------------------------
    | Employee worked Sunday
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '/employee\s+(work|worked)\s+.*sunday/i',
            $lower
        ) ||
        preg_match(
            '/sunday\s+.*(work|worked)\s+.*employee/i',
            $lower
        ) ||
        preg_match(
            '/work(ed)?\s+.*sunday/i',
            $lower
        )
    ) {

        $topic =
            $context['topic'] ?? 'working_hours';

        return [
            'message' =>
                'Got it. I understand this is about an employee working on Sunday.' .
                "\n\n" .
                'Was Sunday their scheduled rest day?',

            'options' => [
                [
                    'label' => 'Yes, it was their rest day',
                    'value' => 'employee worked on rest day sunday',
                    'specifics' => [
                        'rest day',
                        'sunday'
                    ]
                ],
                [
                    'label' => 'No, it was a regular workday',
                    'value' => 'employee worked sunday regular workday',
                    'specifics' => [
                        'regular workday',
                        'sunday'
                    ]
                ],
                [
                    'label' => "I'm not sure",
                    'value' => 'employee worked sunday not sure',
                    'specifics' => [
                        'sunday'
                    ]
                ]
            ],

            'context' => array_merge(
                $context,
                [
                    'topic' => $topic,
                    'ambiguity' => 'sunday_rest_day'
                ]
            )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sunday + rest day
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '/sunday\s+.*rest/i',
            $lower
        ) ||
        preg_match(
            '/rest\s+day\s+.*sunday/i',
            $lower
        )
    ) {

        return [
            'message' =>
                "Got it. Was Sunday the employee's scheduled rest day, or was it a regular working day that happened to fall on a Sunday?",

            'options' => [
                [
                    'label' => 'Yes, it was their rest day',
                    'value' => 'employee worked on rest day sunday',
                    'specifics' => [
                        'rest day',
                        'sunday'
                    ]
                ],
                [
                    'label' => 'No, it was a regular workday',
                    'value' => 'employee worked sunday regular workday',
                    'specifics' => [
                        'regular workday',
                        'sunday'
                    ]
                ],
                [
                    'label' => "I'm not sure",
                    'value' => 'employee worked sunday not sure',
                    'specifics' => [
                        'sunday'
                    ]
                ]
            ],

            'context' => array_merge(
                $context,
                [
                    'topic' => 'working_hours',
                    'ambiguity' => 'sunday_rest_day'
                ]
            )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Holiday
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '/holiday\s+(concern|problem|issue|question)/i',
            $lower
        ) ||
        $lower === 'holiday concern'
    ) {

        return [
            'message' =>
                'Sure. What type of holiday concern is this?',

            'options' => [
                [
                    'label' => 'Holiday Pay',
                    'value' => 'holiday pay',
                    'specifics' => [
                        'holiday pay'
                    ]
                ],
                [
                    'label' => 'Working on a Holiday',
                    'value' => 'working on a holiday',
                    'specifics' => [
                        'working on holiday'
                    ]
                ],
                [
                    'label' => 'Something Else',
                    'value' => '__other__',
                    'specifics' => []
                ]
            ],

            'context' => array_merge(
                $context,
                [
                    'topic' => 'working_hours'
                ]
            )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Employee worked Saturday/Sunday
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '/^employee\s+(worked|work)\s+(sunday|saturday)\b/i',
            $lower
        )
    ) {

        preg_match(
            '/^employee\s+(worked|work)\s+(sunday|saturday)\b/i',
            $lower,
            $matches
        );

        $day = $matches[2] ?? 'that day';

        return [
            'message' =>
                "Got it. Was {$day} the employee's scheduled rest day?",

            'options' => [
                [
                    'label' => 'Yes, it was their rest day',
                    'value' =>
                        'employee worked on ' .
                        $day .
                        ' rest day',
                    'specifics' => [
                        'rest day',
                        $day
                    ]
                ],
                [
                    'label' => 'No, it was a regular workday',
                    'value' =>
                        'employee worked ' .
                        $day .
                        ' regular workday',
                    'specifics' => [
                        'regular workday',
                        $day
                    ]
                ],
                [
                    'label' => "I'm not sure",
                    'value' =>
                        'employee worked ' .
                        $day .
                        ' not sure',
                    'specifics' => [
                        $day
                    ]
                ]
            ],

            'context' => array_merge(
                $context,
                [
                    'topic' => 'working_hours',
                    'ambiguity' => $day . '_rest_day'
                ]
            )
        ];
    }

    return null;
}

/*
|--------------------------------------------------------------------------
| TOPIC
|--------------------------------------------------------------------------
*/

function detectTopic(string $query): string
{
    $lower = strtolower($query);

    if (
        preg_match(
            '/wage|salary|pay|compensation|minimum wage|overtime|deduction|unpaid|rest day|holiday pay|night differential|13th month|thirteenth month|service incentive/i',
            $lower
        )
    ) {
        return 'salary';
    }

    if (
        preg_match(
            '/leave|maternity|paternity|solo parent|parental|vacation|sick|holiday/i',
            $lower
        )
    ) {
        return 'leave';
    }

    if (
        preg_match(
            '/safety|injury|accident|ppe|hazard|workplace safety|occupational/i',
            $lower
        )
    ) {
        return 'safety';
    }

    if (
        preg_match(
            '/harassment|discrimination|safe spaces|gender|violence|sexual/i',
            $lower
        )
    ) {
        return 'harassment';
    }

    if (
        preg_match(
            '/termination|separation|dismissal|retirement|resignation|constructive/i',
            $lower
        )
    ) {
        return 'termination';
    }

    if (
        preg_match(
            '/contribution|sss|philhealth|pag-?ibig|government contribution/i',
            $lower
        )
    ) {
        return 'contributions';
    }

    if (
        preg_match(
            '/foreign|alien|permit|employment of foreign/i',
            $lower
        )
    ) {
        return 'foreign';
    }

    if (
        preg_match(
            '/contract|agreement|employment contract/i',
            $lower
        )
    ) {
        return 'contract';
    }

    if (
        preg_match(
            '/document|requirement|employee document/i',
            $lower
        )
    ) {
        return 'documents';
    }

    if (
        preg_match(
            '/union|labor relations|collective bargaining|certification/i',
            $lower
        )
    ) {
        return 'labor_relations';
    }

    if (
        preg_match(
            '/privacy|data privacy|personal data/i',
            $lower
        )
    ) {
        return 'privacy';
    }

    if (
        preg_match(
            '/mental health|wellness/i',
            $lower
        )
    ) {
        return 'mental_health';
    }

    if (
        preg_match(
            '/benefit/i',
            $lower
        )
    ) {
        return 'benefits';
    }

    if (
        preg_match(
            '/hour|shift|night shift|working hours|rest day/i',
            $lower
        )
    ) {
        return 'working_hours';
    }

    if (
        str_contains(
            $lower,
            'workplace concern'
        )
    ) {
        return 'workplace';
    }

    return 'general';
}

/*
|--------------------------------------------------------------------------
| QUICK REPLIES
|--------------------------------------------------------------------------
*/

function getQuickRepliesForTopic(string $topic): array
{
    switch ($topic) {

        case 'salary':
            return [
                [
                    'label' => 'Minimum Wage',
                    'value' => 'minimum wage'
                ],
                [
                    'label' => 'Overtime Pay',
                    'value' => 'overtime pay'
                ],
                [
                    'label' => 'Holiday Pay',
                    'value' => 'holiday pay'
                ],
                [
                    'label' => 'Rest Day Pay',
                    'value' => 'rest day pay'
                ],
                [
                    'label' => 'Salary Deductions',
                    'value' => 'salary deductions'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];

        case 'leave':
            return [
                [
                    'label' => 'Maternity Leave',
                    'value' => 'maternity leave'
                ],
                [
                    'label' => 'Paternity Leave',
                    'value' => 'paternity leave'
                ],
                [
                    'label' => 'Solo Parent Leave',
                    'value' => 'solo parent leave'
                ],
                [
                    'label' => 'Vacation Leave',
                    'value' => 'vacation leave'
                ],
                [
                    'label' => 'Sick Leave',
                    'value' => 'sick leave'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];

        case 'safety':
            return [
                [
                    'label' => 'Workplace Injury',
                    'value' => 'workplace injury'
                ],
                [
                    'label' => 'Lack of PPE',
                    'value' => 'lack of PPE'
                ],
                [
                    'label' => 'Hazard Identification',
                    'value' => 'hazard identification'
                ],
                [
                    'label' => 'Safety Violations',
                    'value' => 'safety violations'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];

        case 'harassment':
            return [
                [
                    'label' => 'Sexual Harassment',
                    'value' => 'sexual harassment'
                ],
                [
                    'label' => 'Gender-Based Harassment',
                    'value' => 'gender-based harassment'
                ],
                [
                    'label' => 'Discrimination',
                    'value' => 'discrimination'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];

        case 'termination':
            return [
                [
                    'label' => 'Employee Termination',
                    'value' => 'employee termination'
                ],
                [
                    'label' => 'Separation Pay',
                    'value' => 'separation pay'
                ],
                [
                    'label' => 'Retirement',
                    'value' => 'retirement'
                ],
                [
                    'label' => 'Regularization',
                    'value' => 'regularization'
                ],
                [
                    'label' => 'Due Process',
                    'value' => 'due process'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];

        case 'contributions':
            return [
                [
                    'label' => 'SSS',
                    'value' => 'SSS concern'
                ],
                [
                    'label' => 'PhilHealth',
                    'value' => 'PhilHealth concern'
                ],
                [
                    'label' => 'Pag-IBIG',
                    'value' => 'Pag-IBIG concern'
                ],
                [
                    'label' => 'BIR',
                    'value' => 'BIR concern'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];

        case 'benefits':
            return [
                [
                    'label' => 'SSS',
                    'value' => 'SSS benefits'
                ],
                [
                    'label' => 'PhilHealth',
                    'value' => 'PhilHealth benefits'
                ],
                [
                    'label' => 'Pag-IBIG',
                    'value' => 'Pag-IBIG benefits'
                ],
                [
                    'label' => 'BIR',
                    'value' => 'BIR benefits'
                ],
                [
                    'label' => 'Maternity',
                    'value' => 'maternity benefits'
                ],
                [
                    'label' => 'Paternity',
                    'value' => 'paternity benefits'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];

        case 'working_hours':
            return [
                [
                    'label' => 'Overtime',
                    'value' => 'overtime hours'
                ],
                [
                    'label' => 'Rest Day',
                    'value' => 'rest day work'
                ],
                [
                    'label' => 'Holiday Work',
                    'value' => 'holiday work'
                ],
                [
                    'label' => 'Night Shift',
                    'value' => 'night shift'
                ],
                [
                    'label' => 'Working Hours',
                    'value' => 'working hours'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];

        default:
            return [
                [
                    'label' => 'Wages & Salary',
                    'value' => 'salary concern'
                ],
                [
                    'label' => 'Benefits',
                    'value' => 'benefits concern'
                ],
                [
                    'label' => 'Leave',
                    'value' => 'leave concern'
                ],
                [
                    'label' => 'Working Hours',
                    'value' => 'working hours concern'
                ],
                [
                    'label' => 'Government Contributions',
                    'value' => 'government contributions concern'
                ],
                [
                    'label' => 'Workplace Concern',
                    'value' => 'workplace concern'
                ],
                [
                    'label' => 'Other',
                    'value' => '__other__'
                ]
            ];
    }
}

/*
|--------------------------------------------------------------------------
| CLARIFICATION MESSAGE
|--------------------------------------------------------------------------
*/

function getClarificationMessage(string $topic): string
{
    switch ($topic) {

        case 'salary':
            return 'Got it! What part of the salary concern are you checking?';

        case 'leave':
            return 'Sure! Which leave are you asking about?';

        case 'safety':
            return 'I can help with that. What is the issue mainly about? For example: workplace injury, lack of PPE, hazard identification, or safety violations.';

        case 'harassment':
            return 'Sure. Is the concern about sexual harassment, gender-based harassment, discrimination, or another workplace conduct issue?';

        case 'termination':
            return 'Got it. Is the concern about employee termination, separation pay, retirement, regularization, or due process?';

        case 'contributions':
            return 'Sure. Is the concern about SSS, PhilHealth, Pag-IBIG, or other government contribution compliance?';

        case 'benefits':
            return 'Sure! Which benefit are you asking about?';

        case 'working_hours':
            return 'Got it. What are you trying to check?';

        case 'foreign':
            return 'I can help narrow that down. Is the concern about Alien Employment Permits, labor market testing, or work permit compliance for foreign nationals?';

        case 'contract':
            return 'Sure. Is the concern about employment contracts, contract renewal, termination clauses, or another contractual matter?';

        case 'documents':
            return 'Got it. Is the concern about employee document requirements, onboarding documents, or compliance documentation?';

        case 'labor_relations':
            return 'Sure. Is the concern about unions, collective bargaining, certification elections, or labor disputes?';

        case 'privacy':
            return 'Got it. Is the concern about employee data protection, data breach, consent, or privacy compliance?';

        case 'mental_health':
            return 'Sure. Is the concern about workplace mental health programs, employee support, or mental health-related accommodations?';

        default:
            return 'I can help with that. Could you tell me a little more about the concern? For example: salary or minimum wage, leave or employee benefits, workplace safety, harassment or discrimination, employee documents, termination or separation, government contributions, or working hours.';
    }
}

/*
|--------------------------------------------------------------------------
| FORMAT REFERENCE
|--------------------------------------------------------------------------
*/

function formatReference(
    string $query,
    array $reference
): array {

    return [
        'id' => (int)($reference['id'] ?? 0),
        'title' => $reference['title'] ?? '',
        'short_title' => $reference['short_title'] ?? '',
        'reference_number' => $reference['reference_number'] ?? '',
        'reference_type' => $reference['reference_type'] ?? '',
        'issuing_authority' => $reference['issuing_authority'] ?? '',
        'status' => $reference['status'] ?? '',
        'source_url' => $reference['source_url'] ?? '',
        'matched_concepts' => generateMatchedConcepts(
            $query,
            $reference
        ),
        'category_name' => $reference['category_name'] ?? '',
        'summary' => $reference['summary'] ?? ''
    ];
}

/*
|--------------------------------------------------------------------------
| MATCHED CONCEPTS
|--------------------------------------------------------------------------
*/

function generateMatchedConcepts(
    string $query,
    array $reference
): string {

    $words = preg_split(
        '/\s+/',
        strtolower($query),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if (empty($words)) {
        return 'Your concern mentions terms that appear in this reference.';
    }

    $haystack = strtolower(
        ($reference['keywords'] ?? '') .
        ' ' .
        ($reference['title'] ?? '') .
        ' ' .
        ($reference['summary'] ?? '') .
        ' ' .
        ($reference['short_title'] ?? '')
    );

    $matched = [];

    foreach ($words as $word) {

        $word = trim(
            $word,
            " \t\n\r\0\x0B.,!?;:\"'()[]{}"
        );

        if (strlen($word) < 3) {
            continue;
        }

        if (str_contains($haystack, $word)) {
            $matched[] = $word;
        }
    }

    $matched = array_values(
        array_unique($matched)
    );

    if (empty($matched)) {
        return 'Your concern mentions terms that appear in this reference.';
    }

    $examples = array_slice(
        $matched,
        0,
        4
    );

    return 'Your concern mentions ' .
        implode(', ', $examples) .
        ', which are concepts associated with this reference.';
}

/*
|--------------------------------------------------------------------------
| CONFIDENCE
|--------------------------------------------------------------------------
*/

function evaluateMatchConfidence(
    string $query,
    array $result
): string {

    $lower = strtolower($query);

    $keywords =
        strtolower($result['keywords'] ?? '');

    $title =
        strtolower($result['title'] ?? '');

    $shortTitle =
        strtolower($result['short_title'] ?? '');

    $summary =
        strtolower($result['summary'] ?? '');

    $description =
        strtolower($result['description'] ?? '');

    $relatedLaw =
        strtolower($result['related_law'] ?? '');

    $categoryName =
        strtolower($result['category_name'] ?? '');

    $combined =
        $keywords .
        ' ' .
        $title .
        ' ' .
        $shortTitle .
        ' ' .
        $summary .
        ' ' .
        $description .
        ' ' .
        $relatedLaw;

    $words = preg_split(
        '/\s+/',
        $lower,
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if (empty($words)) {
        return 'low';
    }

    $matchedCount = 0;

    foreach ($words as $word) {

        $word = trim(
            $word,
            " \t\n\r\0\x0B.,!?;:\"'()[]{}"
        );

        if (strlen($word) < 3) {
            continue;
        }

        if (str_contains($combined, $word)) {
            $matchedCount++;
        }
    }

    $validWords = array_filter(
        $words,
        static function ($word) {
            $word = trim(
                $word,
                " \t\n\r\0\x0B.,!?;:\"'()[]{}"
            );

            return strlen($word) >= 3;
        }
    );

    $totalWords = count($validWords);

    if ($totalWords === 0) {
        return 'low';
    }

    $matchRatio =
        $matchedCount / $totalWords;

    $topic = detectTopic($query);

    $categoryMatch = false;

    if (
        $topic !== 'general' &&
        $categoryName !== ''
    ) {

        $topicCategoryMap = [
            'salary' => [
                'labor laws',
                'wage orders',
                'labor advisories'
            ],

            'leave' => [
                'labor laws',
                'labor advisories'
            ],

            'safety' => [
                'labor laws',
                'department orders'
            ],

            'harassment' => [
                'labor laws',
                'department orders'
            ],

            'termination' => [
                'labor laws'
            ],

            'contributions' => [
                'labor laws',
                'labor advisories'
            ],

            'foreign' => [
                'department orders',
                'labor laws'
            ],

            'contract' => [
                'labor laws'
            ],

            'documents' => [
                'department orders',
                'memorandum'
            ],

            'labor_relations' => [
                'labor laws',
                'department orders'
            ],

            'privacy' => [
                'labor laws'
            ],

            'mental_health' => [
                'labor laws',
                'department orders'
            ],

            'benefits' => [
                'labor laws',
                'labor advisories'
            ],

            'working_hours' => [
                'labor laws',
                'wage orders',
                'department orders'
            ]
        ];

        $expectedCategories =
            $topicCategoryMap[$topic] ?? [];

        foreach ($expectedCategories as $expected) {

            if (
                str_contains(
                    $categoryName,
                    $expected
                )
            ) {
                $categoryMatch = true;
                break;
            }
        }
    }

    $relevance =
        isset($result['relevance'])
            ? (float)$result['relevance']
            : 0;

    if (
        $matchRatio >= 0.5 &&
        $matchedCount >= 3 &&
        $relevance >= 10
    ) {
        return 'high';
    }

    if (
        $categoryMatch &&
        $matchRatio >= 0.4 &&
        $matchedCount >= 2 &&
        $relevance >= 8
    ) {
        return 'high';
    }

    if (
        $matchedCount >= 2 ||
        $relevance >= 5
    ) {
        return 'partial';
    }

    return 'low';
}

/*
|--------------------------------------------------------------------------
| FOLLOW-UP CLOSING
|--------------------------------------------------------------------------
*/

function isFollowUpClosing(string $query): bool
{
    $lower = strtolower(trim($query));

    $closing = [
        'no that is all',
        "no that's all",
        'no more',
        'no thank you',
        'no thanks',
        'none',
        'nothing else',
        "that's it",
        'that is it',
        'all good',
        'all set',
        'no further',
        'no more questions',
        'no question',
        'no need'
    ];

    foreach ($closing as $phrase) {

        if (
            $lower === $phrase ||
            str_contains($lower, $phrase)
        ) {
            return true;
        }
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| FOLLOW-UP CONTINUE
|--------------------------------------------------------------------------
*/

function isFollowUpContinue(string $query): bool
{
    $lower = strtolower(trim($query));

    $continue = [
        'yes another question',
        'yes i have another question',
        'yes one more',
        'yes more',
        'yes please',
        'yes sure',
        'another',
        'more question',
        'another question',
        'one more question',
        'yes thanks',
        'yes thank you'
    ];

    foreach ($continue as $phrase) {

        if (
            $lower === $phrase ||
            str_contains($lower, $phrase)
        ) {
            return true;
        }
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| IDLE YES
|--------------------------------------------------------------------------
*/

function isIdleCheckYes(string $query): bool
{
    $lower = strtolower(trim($query));

    $yes = [
        'yes',
        'yes still here',
        "yes i'm still here",
        'yes im still here',
        'yes here',
        'still here',
        "i'm still here",
        'im still here',
        'yes please',
        'yes sure',
        'yes continue',
        'continue'
    ];

    foreach ($yes as $phrase) {

        if (
            $lower === $phrase ||
            str_contains($lower, $phrase)
        ) {
            return true;
        }
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| IDLE NO
|--------------------------------------------------------------------------
*/

function isIdleCheckNo(string $query): bool
{
    $lower = strtolower(trim($query));

    $no = [
        'no',
        "no that's all",
        'no that is all',
        'no more',
        'no thank you',
        'no thanks',
        'none',
        'nothing else',
        "that's it",
        'that is it',
        'all good',
        'all set',
        'no further',
        'no more questions',
        'no question',
        'no need',
        'close',
        'end',
        'bye'
    ];

    foreach ($no as $phrase) {

        if (
            $lower === $phrase ||
            str_contains($lower, $phrase)
        ) {
            return true;
        }
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| DEVELOPER QUERY
|--------------------------------------------------------------------------
*/

function isDeveloperQuery(string $query): bool
{
    $lower = strtolower($query);

    $baseKeywords = [
        'developer',
        'creator',
        'created by',
        'made by',
        'who made',
        'who created',
        'programmer',
        'scrum master',
        'document specialist',
        'business analyst',
        'maria cheska jalotjot',
        'phil vincent lope',
        'marycris eliaga',
        'johna mhae azucena',
        'team',
        'members',
        'author',
        'built by',
        'developed by',
        'lala ai team',
        'dev',
        'devs',
        'coder',
        'engineer',
        'architect',
        'designer',
        'who built',
        'who coded',
        'who designed',
        'who programmed',
        'your team',
        'your creators',
        'your developers',
        'your programmers',
        'who is your developer',
        'who is ur developer',
        'whoz ur developer',
        'who is the developer',
        'who is the creator'
    ];

    $typoTolerant = [
        'devloper',
        'devoloper',
        'develloper',
        'devlper',
        'devolepr',
        'creater',
        'creatr',
        'prgrammer',
        'programer',
        'progammer',
        'scrrum',
        'scruum',
        'doc specialist',
        'bussiness analyst',
        'bussines analyst',
        'jalotjot',
        'lope phil',
        'eliaga mary',
        'azucena johna',
        'tean',
        'teem',
        'memebrs',
        'auther',
        'builtin'
    ];

    foreach (
        array_merge(
            $baseKeywords,
            $typoTolerant
        ) as $keyword
    ) {

        if (
            str_contains(
                $lower,
                $keyword
            )
        ) {
            return true;
        }
    }

    $patterns = [
        '/who\s+is\s+your\s+(dev|developer|lala|system|app|module)/i',
        '/who\s+(dev|build|made|created|creat)/i',
        '/your\s+(dev|developer|creator|programmer)/i',
        '/tell\s+me\s+about\s+(dev|developer|creator|programmer|team)/i',
        '/who\s+(are|r)\s+(u|you)\s+(made|built|build|created|developed)/i'
    ];

    foreach ($patterns as $pattern) {

        if (
            preg_match(
                $pattern,
                $lower
            )
        ) {
            return true;
        }
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| DEVELOPER INFORMATION
|--------------------------------------------------------------------------
*/

function getDeveloperInfo(): string
{
    $members = [
        [
            'role' => 'Programmer',
            'name' => 'MARIA CHESKA JALOTJOT',
            'email' => 'jalotjot.cheska@icloud.com',
            'desc' =>
                'Develops and maintains the Legal and Compliance Management Module, implements system functionalities, and performs coding, testing, and debugging.'
        ],
        [
            'role' => 'Scrum Master',
            'name' => 'PHIL VINCENT LOPE',
            'email' => 'lope.philvince1105@gmail.com',
            'desc' =>
                'Facilitates team coordination, monitors sprint progress, and serves as a bridge between stakeholders and the development team.'
        ],
        [
            'role' => 'Document Specialist',
            'name' => 'MARYCRIS ELIAGA',
            'email' => 'eliagamarycris@gmail.com',
            'desc' =>
                'Analyzes system requirements, evaluates existing HR legal and compliance processes, and ensures that the system design supports organizational needs.'
        ],
        [
            'role' => 'Business Analyst',
            'name' => 'JOHNA MHAE AZUCENA',
            'email' => 'johnaazucena@gmail.com',
            'desc' =>
                "Identifies business requirements, coordinates with stakeholders, and ensures that the module aligns with the Human Resource Department's legal and compliance management needs."
        ]
    ];

    $html =
        '<div class="ll-developer-info">' .
        '<div class="ll-developer-header">Development Team</div>' .
        '<div class="ll-developer-list">';

    foreach ($members as $member) {

        $html .=
            '<div class="ll-developer-card">' .

            '<div class="ll-developer-card-role">' .
            htmlspecialchars(
                $member['role'],
                ENT_QUOTES,
                'UTF-8'
            ) .
            '</div>' .

            '<div class="ll-developer-card-name">' .
            htmlspecialchars(
                $member['name'],
                ENT_QUOTES,
                'UTF-8'
            ) .
            '</div>' .

            '<div class="ll-developer-card-contact">' .
            '<a href="mailto:' .
            htmlspecialchars(
                $member['email'],
                ENT_QUOTES,
                'UTF-8'
            ) .
            '">' .
            htmlspecialchars(
                $member['email'],
                ENT_QUOTES,
                'UTF-8'
            ) .
            '</a>' .
            '</div>' .

            '<div class="ll-developer-card-desc">' .
            htmlspecialchars(
                $member['desc'],
                ENT_QUOTES,
                'UTF-8'
            ) .
            '</div>' .

            '</div>';
    }

    $html .=
        '</div>' .
        '</div>';

    return $html;
}
