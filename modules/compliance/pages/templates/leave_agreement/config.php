<?php

return [
    'template_code' => 'leave_agreement',
    'name' => 'Leave Agreement',
    'source_table' => 'em_employees',
    'fields' => [
        'leave_type' => [
            'label' => 'Leave Type',
            'type' => 'select',
            'options' => [
                'Vacation Leave',
                'Sick Leave',
                'Emergency Leave',
                'Maternity Leave',
                'Paternity Leave',
                'Bereavement Leave',
                'Study Leave',
                'Solo Parent Leave',
                'Special Leave for Women',
                'Personal Leave',
            ],
            'required' => true,
        ],
        'leave_start_date' => [
            'label' => 'Leave Start Date',
            'type' => 'date',
            'required' => true,
        ],
        'leave_end_date' => [
            'label' => 'Leave End Date',
            'type' => 'date',
            'required' => true,
        ],
        'leave_duration' => [
            'label' => 'Duration',
            'type' => 'text',
            'required' => false,
        ],
    ],
];

