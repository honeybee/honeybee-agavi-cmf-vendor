<?php

return [
    'method' => 'PUT',
    'uri' => '/test-db/'.$fixture_data['identifier'].'-1',
    'headers' => [ 'Accept' => [ 'application/json' ], 'Content-Type' => [ 'application/json' ] ],
    'body' => [
        '@type' => 'Honeybee\SystemAccount\User\Model\Task\CreateUser\UserCreatedEvent',
        'data' => [
            'identifier' => $fixture_data['identifier'],
            'uuid' => $fixture_data['uuid'],
            'language' => 'de_DE',
            'version' => 1,
            'workflow_state' => 'inactive',
            'username' => 'test user',
            'email' => 'honeybee.user@test.com',
            'role' => 'administrator',
            'firstname' => 'Brock',
            'lastname' => 'Lesnar',
            'auth_token' => $fixture_data['auth_token'],
            'token_expire_date' => $fixture_data['token_expire_date']
        ],
        'aggregate_root_identifier' => $fixture_data['identifier'],
        'aggregate_root_type' => 'honeybee.system_account.user',
        'embedded_entity_events' => [],
        'seq_number' => 1,
        'uuid' => $fixture_data['event_uuid'],
        'iso_date' => $fixture_data['event_iso_date'],
        'metadata' => [
            'user' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
            'role' => 'administrator'
        ]
    ]
];