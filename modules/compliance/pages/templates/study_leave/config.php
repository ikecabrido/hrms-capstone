<?php

return [
    'template_code' => 'study_leave',
    'name' => 'Study Leave Agreement',
    'source_table' => 'em_employees',
    'fields' => [
        'study_program' => [
            'label' => 'Course / Program',
            'type' => 'text',
            'required' => false,
        ],
        'leave_start_date' => [
            'label' => 'Study Leave Period From',
            'type' => 'date',
            'required' => true,
        ],
        'leave_end_date' => [
            'label' => 'Study Leave Period To',
            'type' => 'date',
            'required' => true,
        ],
    ],
];

