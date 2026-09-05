<?php

return [
    'template_code' => 'nte',
    'name' => 'Notice to Explain',
    'source_table' => 'em_employees',
    'fields' => [
        'incident_date' => [
            'label' => 'Date of Incident',
            'type' => 'date',
            'required' => false,
        ],
        'incident_time' => [
            'label' => 'Time',
            'type' => 'text',
            'required' => false,
        ],
        'incident_location' => [
            'label' => 'Location',
            'type' => 'text',
            'required' => false,
        ],
        'incident_description' => [
            'label' => 'Incident Description',
            'type' => 'textarea',
            'required' => false,
        ],
        'policy_violated' => [
            'label' => 'Policy / Rule Violated',
            'type' => 'select',
            'options' => ['Labor Code', 'DOLE Standard', 'Working Hours', 'Benefits', 'Contract', 'Safety', 'Other'],
            'required' => false,
        ],
    ],
];

