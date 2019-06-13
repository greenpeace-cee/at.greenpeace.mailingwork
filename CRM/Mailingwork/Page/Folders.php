<?php
use CRM_Mailingwork_ExtensionUtil as E;

class CRM_Mailingwork_Page_Folders extends CRM_Core_Page {

  public function run() {
    $folders = civicrm_api3('MailingworkFolder', 'get', [
      'options' => ['limit' => 0, 'sort' => 'parent_id, name, id'],
    ])['values'];

    foreach ($folders as $id => $folder) {
      if (!empty($folder['campaign_id'])) {
        $folders[$id]['campaign_id'] = CRM_Core_PseudoConstant::getLabel(
          'CRM_Mailingwork_BAO_MailingworkFolder',
          'campaign_id',
          $folder['campaign_id']
        );
      }

      // how to implement a folder structure with maximum laziness:
      // 1. create a sort_name field that ends up with values like
      //   "parent_folder_1|parent_folder_2|child_folder", then sort by that
      // 2. build a "depth" variable and use it for indentation in template
      // 3. ???
      // 4. Profit!
      $folders[$id]['sort_name'] = $folders[$id]['name'];
      $folders[$id]['depth'] = 0;
      if (!empty($folder['parent_id'])) {
        $parent = $folder;
        while (!is_null($parent)) {
          if (empty($parent['parent_id'])) {
            $parent = NULL;
          } else {
            $parent = civicrm_api3('MailingworkFolder', 'getsingle', [
              'id' => $parent['parent_id'],
            ]);
            $folders[$id]['sort_name'] = "{$parent['name']}|{$folders[$id]['sort_name']}";
            $folders[$id]['depth']++;
          }
        }
      }
    }
    $sort_name = array_column($folders, 'sort_name');
    array_multisort($sort_name, SORT_ASC, $folders);

    $this->assign('rows', $folders);

    // TODO: set default campaign

    parent::run();
  }

}
