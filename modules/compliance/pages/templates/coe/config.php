<?php

return [
    'template_code' => 'coe',
    'name' => 'Certificate of Employment',
    'source_table' => 'em_employees',
    'fields' => [
        'coe_purpose' => [
            'label' => 'Purpose',
            'type' => 'text',
            'required' => false,
        ],
    ],
];

