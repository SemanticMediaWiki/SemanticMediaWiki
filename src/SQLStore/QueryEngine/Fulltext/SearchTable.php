<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\MediaWiki\Database;
use SMW\DataTypeRegistry;
use SMW\SQLStore\SQLStore;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTable {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var boolean
	 */
	private $isEnabled = false;

	/**
	 * @var integer
	 */
	private $minTokenSize = 3;

	/**
	 * @var array
	 */
	private $propertyExemptionList = array();

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->connection = $store->getConnection( 'mw.db.queryengine' );
	}

	/**
	 * @since 2.5
	 *
	 * @param array $propertyExemptionList
	 */
	public function setPropertyExemptionList( array $propertyExemptionList ) {
		$this->propertyExemptionList = array_flip(
			str_replace( ' ', '_', $propertyExemptionList )
		);
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getPropertyExemptionList() {
		return array_keys( $this->propertyExemptionList );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public function isExemptedPropertyById( $id ) {

		$dataItem = $this->store->getObjectIds()->getDataItemById(
			$id
		);

		if ( !$dataItem instanceof DIWikiPage || $dataItem->getDBKey() === '' ) {
			return false;
		}

		return $this->isExemptedProperty(
			DIProperty::newFromUserLabel( $dataItem->getDBKey() )
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function isExemptedProperty( DIProperty $property ) {

		$dataItemTypeId = DataTypeRegistry::getInstance()->getDataItemId(
			$property->findPropertyTypeID()
		);

		// Is neither therefore is exempted
		if ( $dataItemTypeId !== DataItem::TYPE_BLOB && $dataItemTypeId !== DataItem::TYPE_URI ) {
			return true;
		}

		return isset( $this->propertyExemptionList[$property->getKey()] );
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $enabled
	 */
	public function setEnabled( $enabled ) {
		$this->isEnabled = (bool)$enabled;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return $this->isEnabled;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getTableName() {
		return SQLStore::FT_SEARCH_TABLE;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getIndexField() {
		return 'o_text';
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getSortField() {
		return 'o_sort';
	}

	/**
	 * @since 2.5
	 *
	 * @return integer
	 */
	public function getMinTokenSize() {
		return $this->minTokenSize;
	}

	/**
	 * @since 2.5
	 *
	 * @return integer $minTokenSize
	 */
	public function setMinTokenSize( $minTokenSize ) {
		$this->minTokenSize = (int)$minTokenSize;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return integer
	 */
	public function getPropertyIdBy( DIProperty $property ) {
		return $this->store->getObjectIds()->getIDFor( $property->getCanonicalDiWikiPage() );
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getPropertyTables() {
		return $this->store->getPropertyTables();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function addQuotes( $value ) {
		return $this->connection->addQuotes( $value );
	}

}
