<?php
return [
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_user_agent_type',
    'entity'  => 'OptionGroup',
    'cleanup' => 'never',
    'params'  => [
      'version'   => 3,
      'name'      => 'mailingwork_user_agent_type',
      'title'     => 'Mailingwork User Agent Type',
      'data_type' => 'Integer',
      'is_active' => 1,
    ],
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_user_agent',
    'entity'  => 'OptionGroup',
    'cleanup' => 'never',
    'params'  => [
      'version'   => 3,
      'name'      => 'mailingwork_user_agent',
      'title'     => 'Mailingwork User Agent',
      'data_type' => 'Integer',
      'is_active' => 1,
    ],
  ],
];
