<?php
$resources = [
    'predicted-skill-competency-needs' => [
        'eyebrow' => 'Capability planning',
        'title' => 'Predicted skill / Competency needs',
        'description' => 'Review where growing workforce demands may create capability gaps, hiring needs, or learning priorities.',
        'steps' => [
            ['Forecast demand', 'Compare current staffing levels with expected changes in business demand, role complexity, and workload patterns.'],
            ['Gap analysis', 'Highlight critical skills or competencies that may be underrepresented in the current workforce mix.'],
            ['Readiness plan', 'Align recruitment, reskilling, and succession plans to the roles most likely to need support.'],
        ],
    ],
    'implementation-guide' => [
        'eyebrow' => 'Operating model',
        'title' => 'Implementation Guide',
        'description' => 'A practical cycle for turning workforce data into consistent decisions.',
        'steps' => [
            ['Establish a baseline', 'Review the dashboard monthly and record the current headcount, attendance, and turnover measures.'],
            ['Assign ownership', 'Give each department owner responsibility for reviewing its workforce signals and data quality.'],
            ['Investigate changes', 'Use the analytics views to compare trends, identify causes, and prioritize risks.'],
            ['Take action', 'Create a focused plan for hiring, retention, attendance, or inclusion improvements.'],
            ['Measure progress', 'Return to the same measures after implementation and share the outcome in a custom report.'],
        ],
    ],
];

$resource = $resources[$resourcePage] ?? $resources['implementation-guide'];
?>

<section class="wf-resource-page">
    <div class="wf-resource-hero">
        <span class="wa-eyebrow"><?= htmlspecialchars($resource['eyebrow']) ?></span>
        <h1><?= htmlspecialchars($resource['title']) ?></h1>
        <p><?= htmlspecialchars($resource['description']) ?></p>
    </div>
</section>
