<?php

return [
    'template_code' => 'clearance_survey',
    'name' => 'Clearance Survey',
    'source_table' => 'em_employees',
    'fields' => [
        'clearance_date' => [
            'label' => 'Clearance Date',
            'type' => 'date',
            'required' => true,
        ],
    ],
];

