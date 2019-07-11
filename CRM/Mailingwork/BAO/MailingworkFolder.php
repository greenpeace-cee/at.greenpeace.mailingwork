<?php

class CRM_Mailingwork_BAO_MailingworkFolder extends CRM_Mailingwork_DAO_MailingworkFolder {

  /**
   * Get the campaign ID either explicitly set for this folder, inherited from
   * parent(s), or the fallback (default) campaign from settings
   *
   * @param int $folder_id
   *
   * @todo this should really be something like a computed/virtual field so it's
   *       available via API.get without any extra requests. I'm not sure how to
   *       do that in a BAO though ...
   *
   * @return int Effective Campaign ID
   */
  public static function getEffectiveCampaignId($folder_id) {
    while (TRUE) {
      $query = CRM_Core_DAO::executeQuery(
        "SELECT parent_id, campaign_id FROM civicrm_mailingwork_folder WHERE id = %1",
        [
          1 => [$folder_id, 'Integer'],
        ]
      );
      if (!$query->fetch()) {
        return NULL;
      };
      if (!empty($query->campaign_id)) {
        return $query->campaign_id;
      }
      if (empty($query->parent_id)) {
        // we're down to a root folder and found no campaign, use fallback
        return Civi::settings()->get('mailingwork_fallback_campaign');
      }
      $folder_id = $query->parent_id;
    }
  }

}
