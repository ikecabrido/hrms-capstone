<?php

return [
    'template_code' => 'exit_clearance',
    'name' => 'Exit Clearance',
    'source_table' => 'em_employees',
    'fields' => [
        'exit_date' => [
            'label' => 'Date of Separation',
            'type' => 'date',
            'required' => false,
        ],
    ],
];

