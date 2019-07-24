<?php
return [
  'mandrill_webhook_key' => [
    'group_name'  => 'domain',
    'name'        => 'mandrill_webhook_key',
    'title'       => ts('Mandrill webhook key'),
    'description' => ts('The Mandrill webhook key is found listed with your webhook at https://mandrillapp.com/settings/webhooks'),
    'type'        => 'String',
    'add'         => '5.8',
    'html_type'   => 'text',
    'default'     => '',
    'is_domain'   => 1,
    'is_contact'  => 0,
  ]
];
