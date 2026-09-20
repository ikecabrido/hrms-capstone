<?php

return [
    'template_code' => 'employment_contract',
    'name' => 'Employment Contract',
    'source_table' => 'new_hire_table',
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
            'label' => 'Employment Status',
            'type' => 'select',
            'options' => ['Regular', 'Probationary', 'Fixed-Term', 'Project', 'Seasonal', 'Casual', 'Part-Time'],
            'required' => true,
        ],
        'contract_salary_input' => [
            'label' => 'Basic Monthly Salary',
            'type' => 'currency',
            'required' => true,
        ],
    ],
];
