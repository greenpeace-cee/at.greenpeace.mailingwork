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

}
