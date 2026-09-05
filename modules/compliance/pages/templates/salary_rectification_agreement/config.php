<?php

return [
    'template_code' => 'salary_rectification_agreement',
    'name' => 'Salary Rectification Agreement',
    'source_table' => 'em_employees',
    'fields' => [
        'contract_start_date' => [
            'label' => 'Original Contract Date',
            'type' => 'date',
            'required' => true,
        ],
        'contract_end_date' => [
            'label' => 'Effectivity Date',
            'type' => 'date',
            'required' => true,
        ],
        'contract_salary_input' => [
            'label' => 'Corrected Salary',
            'type' => 'currency',
            'required' => true,
        ],
    ],
];

