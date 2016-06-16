<?php

return [
    'index' => '%honeybee-system_account.index%',
    'type' => '%honeybee-system_account-user.standard.type%',
    'id' => $dynamic_data['identifier'],
    'body' => [
        '@type' => 'honeybee.system_account.user',
        'identifier' => $dynamic_data['identifier'],
        'revision' => 1,
        'uuid' => $dynamic_data['uuid'],
        'short_id' => 0,
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => $dynamic_data['event_iso_date'],
        'modified_at' => $dynamic_data['event_iso_date'],
        'workflow_state' => 'inactive',
        'workflow_parameters' => [],
        'metadata' => [
            'user' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
            'role' => 'administrator'
        ],
        'username' => 'test user',
        'email' => 'honeybee.user@test.com',
        'role' => 'administrator',
        'firstname' => 'Brock',
        'lastname' => 'Lesnar',
        'password_hash' => '',
        'background_images' => [],
        'auth_token' => $dynamic_data['auth_token'],
        'token_expire_date' => $dynamic_data['token_expire_date']
    ],
    'refresh' => true
];