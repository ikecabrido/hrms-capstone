<?php

return [
    'template_code' => 'suspension_notice',
    'name' => 'Suspension Notice',
    'source_table' => 'em_employees',
    'fields' => [
        'contract_start_date' => [
            'label' => 'Suspension Start Date',
            'type' => 'date',
            'required' => false,
        ],
        'contract_end_date' => [
            'label' => 'Suspension End Date',
            'type' => 'date',
            'required' => false,
        ],
    ],
];

