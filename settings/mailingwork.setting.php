<?php

use CRM_Mailingwork_ExtensionUtil as E;

return [
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
  'mailingwork_use_mass_activities' => [
    'name'            => 'mailingwork_use_mass_activities',
    'type'            => 'Boolean',
    'default'         => TRUE,
    'quick_form_type' => 'YesNo',
    'html_type'       => 'radio',
    'add'             => '4.7',
    'title'           => E::ts('Use mass activities for Mailingwork recipients'),
    'description'     => E::ts('Whether to create (mass) activities with multiple contacts or one activity per recipient'),
    'is_domain'       => 1,
    'is_contact'      => 0,
  ],
  'mailingwork_retained_emails_last_hardbounce_from' => [
    'name'            => 'mailingwork_retained_emails_last_hardbounce_from',
    'type'            => 'String',
    'default'         => '',
    'quick_form_type' => 'Element',
    'html_type'       => 'text',
    'add'             => '4.7',
    'title'           => E::ts('Mailingwork Retained Emails Last Hardbounce From Date'),
    'description'     => E::ts('Earliest last Hardbounce date which should be imported.'),
    'is_domain'       => 1,
    'is_contact'      => 0,
  ],
];
