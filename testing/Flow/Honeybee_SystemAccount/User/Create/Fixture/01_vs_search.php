<?php

return [
    'from' => 0,
    'size' => 10000,
    'body' => [
        'query' => [
            'filtered' => [
                'query' => [
                    'match_all' => []
                ],
                'filter' => [
                    'and' => [
                        [
                            'not' => [
                                'term' => [
                                    'identifier' => $dynamic_data['identifier']
                                ]
                            ]
                        ],
                        [
                            'not' => [
                                'term' => [
                                    'workflow_state' => 'deleted'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'sort' => []
    ],
    'index' => '%honeybee-system_account.index%',
    'type' => '%honeybee-system_account-user.standard.type%'
];
