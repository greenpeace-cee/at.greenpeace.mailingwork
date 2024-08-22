<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Mailingwork_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Add bounce_sync_date and bounce_sync_status_id
   *
   * @return bool
   */
  public function upgrade_0100() {
    $this->addTask(
      'Add bounce_sync_date to civicrm_mailingwork_mailing',
      'addColumn',
      'civicrm_mailingwork_mailing',
      'bounce_sync_date',
      "datetime NULL   COMMENT 'Date until which bounces have been synced'"
    );
    $this->addTask(
      'Add bounce_sync_status_id to civicrm_mailingwork_mailing',
      'addColumn',
      'civicrm_mailingwork_mailing',
      'bounce_sync_status_id',
      "int unsigned NOT NULL  DEFAULT 1 COMMENT 'ID of sync status'"
    );
    return TRUE;
  }

  public function upgrade_0110() {
    $this->ctx->log->info('Applying update 0110');
    CRM_Core_DAO::executeQuery("CREATE TABLE `civicrm_mailingwork_opening` (
      `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique MailingworkOpening ID',
      `activity_contact_id` int unsigned NOT NULL   COMMENT 'FK to ActivityContact',
      `opening_date` datetime NOT NULL   COMMENT 'Date of the opening',
      `user_agent_type_id` int unsigned NULL  DEFAULT 1 COMMENT 'ID of user agent type',
      `user_agent_id` int unsigned NULL  DEFAULT 1 COMMENT 'ID of user agent',
      PRIMARY KEY (`id`),
      CONSTRAINT FK_civicrm_mailingwork_opening_activity_contact_id FOREIGN KEY (`activity_contact_id`) REFERENCES `civicrm_activity_contact`(`id`) ON DELETE CASCADE)");
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    return TRUE;
  }

  public function upgrade_0120() {
    $this->ctx->log->info('Applying update 0120');

    // MailingworkInterest
    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_mailingwork_interest` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique MailingworkInterest ID',
        `name` varchar(255) NULL COMMENT 'Name of the Interest',
        `mailingwork_id` int unsigned NOT NULL COMMENT 'Unique Identifier used by Mailingwork',
        PRIMARY KEY (`id`)
      )
    ");

    // MailingworkLink
    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_mailingwork_link` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique MailingworkLink ID',
        `url` varchar(1023) NULL COMMENT 'URL of the Link',
        `mailingwork_id` int unsigned NOT NULL COMMENT 'Unique Identifier used by Mailingwork',
        `mailing_id` int unsigned NOT NULL COMMENT 'FK to MailingworkMailing',
        PRIMARY KEY (`id`),
        CONSTRAINT FK_civicrm_mailingwork_link_mailing_id FOREIGN KEY (`mailing_id`) REFERENCES `civicrm_mailingwork_mailing`(`id`) ON DELETE CASCADE
      )
    ");

    // MailingworkLinkInterest
    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_mailingwork_link_interest` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique MailingworkLinkInterest ID',
        `link_id` int unsigned NOT NULL COMMENT 'FK to MailingworkLink',
        `interest_id` int unsigned NOT NULL COMMENT 'FK to MailingworkInterest',
        PRIMARY KEY (`id`),
        CONSTRAINT FK_civicrm_mailingwork_link_interest_link_id FOREIGN KEY (`link_id`) REFERENCES `civicrm_mailingwork_link`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_mailingwork_link_interest_interest_id FOREIGN KEY (`interest_id`) REFERENCES `civicrm_mailingwork_interest`(`id`) ON DELETE CASCADE
      )
    ");

    // MailingworkClick
    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_mailingwork_click` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique MailingworkClick ID',
        `click_date` datetime NULL COMMENT 'Date of the click',
        `activity_contact_id` int unsigned NOT NULL COMMENT 'FK to ActivityContact',
        `link_id` int unsigned NOT NULL COMMENT 'FK to MailingworkLink',
        PRIMARY KEY (`id`),
        CONSTRAINT FK_civicrm_mailingwork_click_activity_contact_id FOREIGN KEY (`activity_contact_id`) REFERENCES `civicrm_activity_contact`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_mailingwork_click_link_id FOREIGN KEY (`link_id`) REFERENCES `civicrm_mailingwork_link`(`id`) ON DELETE CASCADE
      )
    ");

    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();

    return TRUE;
  }

}
