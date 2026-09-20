<?php

return [
    'template_code' => 'return_to_work_agreement',
    'name' => 'Return to Work Agreement',
    'source_table' => 'em_employees',
    'fields' => [
        'leave_reason' => [
            'label' => 'Reason for Leave',
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
    ],
];

