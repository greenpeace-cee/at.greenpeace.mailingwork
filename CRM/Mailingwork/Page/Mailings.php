<?php

class CRM_Mailingwork_Page_Mailings extends CRM_Core_Page {

  public function run() {

    $mailings = civicrm_api3('MailingworkMailing', 'get', [
      'api.MailingworkMailing.getcampaign' => [],
      'options' => [
        'limit' => 0,
        'sort' => 'sending_date DESC, mailingwork_identifier DESC',
      ],
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
      $mailings[$id]['bounce_sync_status_id'] = CRM_Core_PseudoConstant::getLabel(
        'CRM_Mailingwork_BAO_MailingworkMailing',
        'bounce_sync_status_id',
        $mailing['bounce_sync_status_id']
      );
      $mailings[$id]['campaign_title'] = $mailing['api.MailingworkMailing.getcampaign']['values']['title'];
    }
    $this->assign('rows', $mailings);

    if (!empty(Civi::settings()->get('mailingwork_fallback_campaign'))) {
      $this->assign('default_campaign', civicrm_api3('Campaign', 'getvalue', [
        'return' => 'title',
        'id'     => Civi::settings()->get('mailingwork_fallback_campaign'),
      ]));
    }

    parent::run();
  }

}
