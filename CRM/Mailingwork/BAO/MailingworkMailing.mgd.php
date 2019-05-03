<?php
return [
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_status',
    'entity'  => 'OptionGroup',
    'cleanup' => 'never',
    'params'  => [
      'version'   => 3,
      'name'      => 'mailingwork_mailing_status',
      'title'     => 'Mailingwork Mailing Status',
      'data_type' => 'Integer',
      'is_active' => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_sync_status',
    'entity'  => 'OptionGroup',
    'cleanup' => 'never',
    'params'  => [
      'version'   => 3,
      'name'      => 'mailingwork_mailing_sync_status',
      'title'     => 'Mailingwork Mailing Synchronization Status',
      'data_type' => 'Integer',
      'is_active' => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_type',
    'entity'  => 'OptionGroup',
    'cleanup' => 'never',
    'params'  => [
      'version'   => 3,
      'name'      => 'mailingwork_mailing_type',
      'title'     => 'Mailingwork Mailing Type',
      'data_type' => 'Integer',
      'is_active' => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_status_drafted',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_status',
      'value'           => 1,
      'name'            => 'drafted',
      'label'           => 'Drafted',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_status_activated',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_status',
      'value'           => 2,
      'name'            => 'activated',
      'label'           => 'Activated',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_status_paused',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_status',
      'value'           => 3,
      'name'            => 'paused',
      'label'           => 'Paused',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_status_done',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_status',
      'value'           => 4,
      'name'            => 'done',
      'label'           => 'Done',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_status_cancelled',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_status',
      'value'           => 5,
      'name'            => 'cancelled',
      'label'           => 'Cancelled',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_type_standard',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_type',
      'value'           => 1,
      'name'            => 'standard',
      'label'           => 'Standard',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_type_dialog',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_type',
      'value'           => 2,
      'name'            => 'dialog',
      'label'           => 'Dialog',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_type_campaign',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_type',
      'value'           => 3,
      'name'            => 'campaign',
      'label'           => 'Campaign',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_sync_status_pending',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_sync_status',
      'value'           => 1,
      'name'            => 'pending',
      'label'           => 'Pending',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_sync_status_in_progress',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_sync_status',
      'value'           => 2,
      'name'            => 'in_progress',
      'label'           => 'In Progress',
      'is_active'       => 1,
    ]
  ],
  [
    'module'  => 'at.greenpeace.mailingwork',
    'name'    => 'mailingwork_mailing_sync_status_completed',
    'entity'  => 'OptionValue',
    'cleanup' => 'never',
    'params'  => [
      'version'         => 3,
      'option_group_id' => 'mailingwork_mailing_sync_status',
      'value'           => 3,
      'name'            => 'completed',
      'label'           => 'Completed',
      'is_active'       => 1,
    ]
  ],
];