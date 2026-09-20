<?php

return [
    'template_code' => 'training_bond',
    'name' => 'Training Bond Agreement',
    'source_table' => 'em_employees',
    'fields' => [
        'training_program' => [
            'label' => 'Training Program',
            'type' => 'text',
            'required' => false,
        ],
        'bond_period' => [
            'label' => 'Bond Period',
            'type' => 'text',
            'required' => false,
        ],
        'agreement_date' => [
            'label' => 'Agreement Date',
            'type' => 'date',
            'required' => false,
        ],
    ],
];

