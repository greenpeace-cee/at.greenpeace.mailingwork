<?php
use CRM_Mailingwork_ExtensionUtil as E;

class CRM_Mailingwork_Page_Mailings extends CRM_Core_Page {

  public function run() {

    $mailings = civicrm_api3('MailingworkMailing', 'get', [
      'options' => ['limit' => 0, 'sort' => 'sending_date DESC, mailingwork_identifier DESC'],
    ])['values'];
    foreach ($mailings as $id => $mailing) {
      $mailings[$id]['type_id'] = CRM_Core_PseudoConstant::getLabel(
        'CRM_Mailingwork_BAO_MailingworkMailing',
        'type_id',
        $mailing['type_id']
      );
      $mailings[$id]['status_id'] = CRM_Core_PseudoConstant::getLabel(
        'CRM_Mailingwork_BAO_MailingworkMailing',
        'status_id',
        $mailing['status_id']
      );
      if (!empty($mailing['mailingwork_folder_id'])) {
        $mailings[$id]['mailingwork_folder_id'] = CRM_Core_PseudoConstant::getLabel(
          'CRM_Mailingwork_BAO_MailingworkMailing',
          'mailingwork_folder_id',
          $mailing['mailingwork_folder_id']
        );
      }
      $mailings[$id]['recipient_sync_status_id'] = CRM_Core_PseudoConstant::getLabel(
        'CRM_Mailingwork_BAO_MailingworkMailing',
        'recipient_sync_status_id',
        $mailing['recipient_sync_status_id']
      );
      $mailings[$id]['opening_sync_status_id'] = CRM_Core_PseudoConstant::getLabel(
        'CRM_Mailingwork_BAO_MailingworkMailing',
        'opening_sync_status_id',
        $mailing['opening_sync_status_id']
      );
      $mailings[$id]['click_sync_status_id'] = CRM_Core_PseudoConstant::getLabel(
        'CRM_Mailingwork_BAO_MailingworkMailing',
        'click_sync_status_id',
        $mailing['click_sync_status_id']
      );
    }
    $this->assign('rows', $mailings);

    parent::run();
  }

}
