<?php
return [
  'mandrill_api_key' => [
    'group_name'  => 'domain',
    'name'        => 'mandrill_api_secret',
    'title'       => ts('Mandrill API secret'),
    'description' => ts('The Mandrill API secret for the webhook'),
    'type'        => 'String',
    'add'         => '5.8',
    'html_type'   => 'text',
    'default'     => '',
    'is_domain'   => 1,
    'is_contact'  => 0,
  ]
];
