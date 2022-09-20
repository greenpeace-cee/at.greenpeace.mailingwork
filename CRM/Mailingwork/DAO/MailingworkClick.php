<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from at.greenpeace.mailingwork/xml/schema/CRM/Mailingwork/MailingworkClick.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:bcdefd3362eff0b269085a3a8ee11611)
 */
use CRM_Mailingwork_ExtensionUtil as E;

/**
 * Database access object for the MailingworkClick entity.
 */
class CRM_Mailingwork_DAO_MailingworkClick extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_mailingwork_click';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = TRUE;

  /**
   * Unique MailingworkClick ID
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $id;

  /**
   * Date of the click
   *
   * @var string
   *   (SQL type: datetime)
   *   Note that values will be retrieved from the database as a string.
   */
  public $click_date;

  /**
   * FK to ActivityContact
   *
   * @var int|string
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $activity_contact_id;

  /**
   * FK to MailingworkLink
   *
   * @var int|string
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $link_id;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_mailingwork_click';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Mailingwork Clicks') : E::ts('Mailingwork Click');
  }

  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'activity_contact_id', 'civicrm_activity_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'link_id', 'civicrm_mailingwork_link', 'id');
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'links_callback', Civi::$statics[__CLASS__]['links']);
    }
    return Civi::$statics[__CLASS__]['links'];
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Click ID'),
          'description' => E::ts('Unique MailingworkClick ID'),
          'required' => TRUE,
          'where' => 'civicrm_mailingwork_click.id',
          'table_name' => 'civicrm_mailingwork_click',
          'entity' => 'MailingworkClick',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkClick',
          'localizable' => 0,
          'html' => [
            'type' => 'Number',
          ],
          'readonly' => TRUE,
          'add' => NULL,
        ],
        'click_date' => [
          'name' => 'click_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Click Date'),
          'description' => E::ts('Date of the click'),
          'required' => FALSE,
          'where' => 'civicrm_mailingwork_click.click_date',
          'table_name' => 'civicrm_mailingwork_click',
          'entity' => 'MailingworkClick',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkClick',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
          ],
          'add' => NULL,
        ],
        'activity_contact_id' => [
          'name' => 'activity_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('ActivityContact ID'),
          'description' => E::ts('FK to ActivityContact'),
          'required' => TRUE,
          'where' => 'civicrm_mailingwork_click.activity_contact_id',
          'table_name' => 'civicrm_mailingwork_click',
          'entity' => 'MailingworkClick',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkClick',
          'localizable' => 0,
          'FKClassName' => 'CRM_Activity_DAO_ActivityContact',
          'html' => [
            'type' => 'Number',
          ],
          'add' => NULL,
        ],
        'link_id' => [
          'name' => 'link_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('MailingworkLink ID'),
          'description' => E::ts('FK to MailingworkLink'),
          'required' => TRUE,
          'where' => 'civicrm_mailingwork_click.link_id',
          'table_name' => 'civicrm_mailingwork_click',
          'entity' => 'MailingworkClick',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkClick',
          'localizable' => 0,
          'FKClassName' => 'CRM_Mailingwork_DAO_MailingworkLink',
          'html' => [
            'type' => 'Number',
          ],
          'add' => NULL,
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'mailingwork_click', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'mailingwork_click', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
