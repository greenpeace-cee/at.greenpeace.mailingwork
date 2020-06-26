<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Mailingwork_Upgrader extends CRM_Mailingwork_Upgrader_Base {

  /**
   * Add a column to a table if it doesn't already exist
   *
   * @param $table
   * @param $column
   * @param $properties
   *
   * @return bool
   */
  protected function addColumn($table, $column, $properties) {
    $queries = [];
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column)) {
      $queries[] = "ALTER TABLE `$table` ADD COLUMN `$column` $properties";
      foreach ($queries as $query) {
        CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);
      }
    }
    return TRUE;
  }

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

}
