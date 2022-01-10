<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from at.greenpeace.mailingwork/xml/schema/CRM/Mailingwork/ActivityContactEmail.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:de847668e5e029699a2537f0dc42f4fa)
 */
use CRM_Mailingwork_ExtensionUtil as E;

/**
 * Database access object for the ActivityContactEmail entity.
 */
class CRM_Mailingwork_DAO_ActivityContactEmail extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_activity_contact_email';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = TRUE;

  /**
   * Unique ActivityContactEmail ID
   *
   * @var int
   */
  public $id;

  /**
   * FK to ActivityContact
   *
   * @var int
   */
  public $activity_contact_id;

  /**
   * email used to communicate with the contact
   *
   * @var string
   */
  public $email;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_activity_contact_email';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Activity Contact Emails') : E::ts('Activity Contact Email');
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
          'description' => E::ts('Unique ActivityContactEmail ID'),
          'required' => TRUE,
          'where' => 'civicrm_activity_contact_email.id',
          'table_name' => 'civicrm_activity_contact_email',
          'entity' => 'ActivityContactEmail',
          'bao' => 'CRM_Mailingwork_DAO_ActivityContactEmail',
          'localizable' => 0,
          'readonly' => TRUE,
          'add' => NULL,
        ],
        'activity_contact_id' => [
          'name' => 'activity_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('FK to ActivityContact'),
          'required' => TRUE,
          'where' => 'civicrm_activity_contact_email.activity_contact_id',
          'table_name' => 'civicrm_activity_contact_email',
          'entity' => 'ActivityContactEmail',
          'bao' => 'CRM_Mailingwork_DAO_ActivityContactEmail',
          'localizable' => 0,
          'FKClassName' => 'CRM_Activity_DAO_ActivityContact',
          'add' => NULL,
        ],
        'email' => [
          'name' => 'email',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Email'),
          'description' => E::ts('email used to communicate with the contact'),
          'required' => FALSE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'where' => 'civicrm_activity_contact_email.email',
          'table_name' => 'civicrm_activity_contact_email',
          'entity' => 'ActivityContactEmail',
          'bao' => 'CRM_Mailingwork_DAO_ActivityContactEmail',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
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
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'activity_contact_email', $prefix, []);
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
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'activity_contact_email', $prefix, []);
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
