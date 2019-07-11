<?php

use CRM_Mailingwork_ExtensionUtil as E;

return [
  'mailingwork_bounce_sync_date' => [
    'name'            => 'mailingwork_bounce_sync_date',
    'type'            => 'String',
    'default'         => '',
    'quick_form_type' => 'Element',
    'html_type'       => 'text',
    'add'             => '4.7',
    'title'           => E::ts('Mailingwork Bounce Synchronization Date'),
    'description'     => E::ts('Date of the most recently synchronized bounce'),
    'is_domain'       => 1,
    'is_contact'      => 0,
  ],
  'mailingwork_fallback_campaign' => [
    'name'            => 'mailingwork_fallback_campaign',
    'type'            => 'Integer',
    'default'         => '',
    'quick_form_type' => 'Element',
    'html_type'       => 'text',
    'add'             => '4.7',
    'title'           => E::ts('Mailingwork Fallback Campaign'),
    'description'     => E::ts('Fallback campaign for Mailingwork folders with no campaign'),
    'is_domain'       => 1,
    'is_contact'      => 0,
  ],
];
