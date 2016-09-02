<?php

return [
    'index' => 'honeybee.honeybee-system_account',
    'type' => 'honeybee-system_account-user-standard',
    'id' => $fixture_data['identifier'],
    'body' => [
        '@type' => 'honeybee.system_account.user::projection.standard',
        'identifier' => $fixture_data['identifier'],
        'revision' => 1,
        'uuid' => $fixture_data['uuid'],
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => $fixture_data['event_iso_date'],
        'modified_at' => $fixture_data['event_iso_date'],
        'workflow_state' => 'inactive',
        'workflow_parameters' => [],
        'metadata' => [
            'user' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
            'role' => 'full-privileged'
        ],
        'username' => 'test user',
        'email' => 'honeybee.user@test.com',
        'role' => 'full-privileged',
        'firstname' => 'Brock',
        'lastname' => 'Lesnar',
        'password_hash' => '',
        'background_images' => [],
        'auth_token' => $fixture_data['auth_token'],
        'token_expire_date' => $fixture_data['token_expire_date']
    ],
    'refresh' => true
];
