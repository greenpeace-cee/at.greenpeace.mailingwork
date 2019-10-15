<?php

class CRM_Mailingwork_BAO_MailingworkMailing extends CRM_Mailingwork_DAO_MailingworkMailing {

  /**
   * Get the campaign ID either explicitly set for this mailing, the folder
   * structure, or the fallback (default) campaign from settings
   *
   * @param int $mailing_id
   *
   * @return int Effective Campaign ID
   * @throws \CiviCRM_API3_Exception
   * @todo this should really be something like a computed/virtual field so it's
   *       available via API.get without any extra requests. I'm not sure how to
   *       do that in a BAO though ...
   *
   */
  public static function getEffectiveCampaignId($mailing_id) {
    $mailing = civicrm_api3('MailingworkMailing', 'getsingle', [
      'return' => 'description,mailingwork_folder_id',
      'id'     => $mailing_id,
    ]);
    if (!empty($mailing['description']) && preg_match('/\[CiviCampaign=(\d+)\]/i', $mailing['description'], $matches)) {
      // found a campaign in description, verify it exists
      try {
        return civicrm_api3('Campaign', 'getvalue', [
          'return' => 'id',
          'id'     => $matches[1],
        ]);
      }
      catch (CiviCRM_API3_Exception $e) {
        Civi::log()->warning('Invalid campaign ID in mailing description: "' . $matches[1] . '", description was "' . $mailing['description'] . '"');
      }
    }
    if (!empty($mailing['mailingwork_folder_id'])) {
      return civicrm_api3('MailingworkFolder', 'getcampaign', [
        'id' => $mailing['mailingwork_folder_id'],
      ])['values']['id'];
    }
    return Civi::settings()->get('mailingwork_fallback_campaign');
  }

}
