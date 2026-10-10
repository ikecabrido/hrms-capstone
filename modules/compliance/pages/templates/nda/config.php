<?php

return [
    'template_code' => 'nda',
    'name' => 'NDA / Confidentiality Agreement',
    'source_table' => 'new_hire_table',
    'fields' => [
        'nda_effective_date' => [
            'label' => 'Effective Date',
            'type' => 'date',
            'required' => false,
        ],
    ],
];
