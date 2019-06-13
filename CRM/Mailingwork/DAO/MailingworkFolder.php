<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 *
 * Generated from /Users/patrick/buildkit/build/epicupgrade/sites/default/files/civicrm/ext/at.greenpeace.mailingwork/xml/schema/CRM/Mailingwork/MailingworkFolder.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:85919aa9775e3f922654ae894c247453)
 */

/**
 * Database access object for the MailingworkFolder entity.
 */
class CRM_Mailingwork_DAO_MailingworkFolder extends CRM_Core_DAO {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  static $_tableName = 'civicrm_mailingwork_folder';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  static $_log = TRUE;

  /**
   * Unique Mailingwork Folder ID
   *
   * @var int unsigned
   */
  public $id;

  /**
   * Unique identifier of folder in Mailingwork
   *
   * @var int unsigned
   */
  public $mailingwork_identifier;

  /**
   * Parent folder
   *
   * @var int unsigned
   */
  public $parent_id;

  /**
   * Folder name
   *
   * @var string
   */
  public $name;

  /**
   * Campaign ID associated with mailings in this folder.
   *
   * @var int unsigned
   */
  public $campaign_id;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_mailingwork_folder';
    parent::__construct();
  }

  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static ::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'parent_id', 'civicrm_mailingwork_folder', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'campaign_id', 'civicrm_campaign', 'id');
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
          'title' => ts('Folder ID'),
          'description' => 'Unique Mailingwork Folder ID',
          'required' => TRUE,
          'table_name' => 'civicrm_mailingwork_folder',
          'entity' => 'MailingworkFolder',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkFolder',
          'localizable' => 0,
        ],
        'mailingwork_identifier' => [
          'name' => 'mailingwork_identifier',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Mailingwork Identifier'),
          'description' => 'Unique identifier of folder in Mailingwork',
          'required' => TRUE,
          'table_name' => 'civicrm_mailingwork_folder',
          'entity' => 'MailingworkFolder',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkFolder',
          'localizable' => 0,
        ],
        'parent_id' => [
          'name' => 'parent_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Parent Folder ID'),
          'description' => 'Parent folder',
          'required' => FALSE,
          'table_name' => 'civicrm_mailingwork_folder',
          'entity' => 'MailingworkFolder',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkFolder',
          'localizable' => 0,
          'pseudoconstant' => [
            'table' => 'civicrm_mailingwork_folder',
            'keyColumn' => 'id',
            'labelColumn' => 'name',
          ]
        ],
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Folder Name'),
          'description' => 'Folder name',
          'required' => FALSE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'table_name' => 'civicrm_mailingwork_folder',
          'entity' => 'MailingworkFolder',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkFolder',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
        ],
        'campaign_id' => [
          'name' => 'campaign_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Campaign'),
          'description' => 'Campaign ID associated with mailings in this folder.',
          'required' => FALSE,
          'table_name' => 'civicrm_mailingwork_folder',
          'entity' => 'MailingworkFolder',
          'bao' => 'CRM_Mailingwork_DAO_MailingworkFolder',
          'localizable' => 0,
          'pseudoconstant' => [
            'table' => 'civicrm_campaign',
            'keyColumn' => 'id',
            'labelColumn' => 'title',
          ]
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
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'mailingwork_folder', $prefix, []);
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
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'mailingwork_folder', $prefix, []);
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
    $indices = [
      'UI_mailingwork_identifier' => [
        'name' => 'UI_mailingwork_identifier',
        'field' => [
          0 => 'mailingwork_identifier',
        ],
        'localizable' => FALSE,
        'unique' => TRUE,
        'sig' => 'civicrm_mailingwork_folder::1::mailingwork_identifier',
      ],
    ];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
