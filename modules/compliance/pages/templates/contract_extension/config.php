<?php

return [
    'template_code' => 'contract_extension',
    'name' => 'Contract Extension',
    'source_table' => 'em_employees',
    'fields' => [
        'contract_start_date' => [
            'label' => 'Contract Start Date',
            'type' => 'date',
            'required' => true,
        ],
        'contract_end_date' => [
            'label' => 'Contract End Date',
            'type' => 'date',
            'required' => true,
        ],
        'contract_type' => [
            'label' => 'Contract Type',
            'type' => 'select',
            'options' => ['Regular', 'Probationary', 'Fixed-Term', 'Project', 'Seasonal', 'Casual', 'Part-Time'],
            'required' => true,
        ],
        'contract_salary_input' => [
            'label' => 'Monthly Salary',
            'type' => 'currency',
            'required' => true,
        ],
    ],
];

