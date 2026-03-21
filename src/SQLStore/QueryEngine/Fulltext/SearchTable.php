<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataTypeRegistry;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;

/**
 * @license GPL-2.0-or-later
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
	 * @var bool
	 */
	private $isEnabled = false;

	/**
	 * @var int
	 */
	private $minTokenSize = 3;

	/**
	 * @var int
	 */
	private $indexableDataTypes = 0;

	/**
	 * @var array
	 */
	private $propertyExemptionList = [];

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
	public function setPropertyExemptionList( array $propertyExemptionList ): void {
		$this->propertyExemptionList = array_flip(
			str_replace( ' ', '_', $propertyExemptionList )
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param int $indexableDataTypes
	 */
	public function setIndexableDataTypes( $indexableDataTypes ): void {
		$this->indexableDataTypes = $indexableDataTypes;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getPropertyExemptionList(): array {
		return array_keys( $this->propertyExemptionList );
	}

	/**
	 * @since 2.5
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function isExemptedPropertyById( $id ) {
		$dataItem = $this->getDataItemById( $id );

		if ( !$dataItem instanceof WikiPage || $dataItem->getDBKey() === '' ) {
			return false;
		}

		try {
			$property = Property::newFromUserLabel(
				$dataItem->getDBKey()
			);
		} catch ( PredefinedPropertyLabelMismatchException $e ) {
			// The property no longer exists (or is no longer available) therefore
			// exempt it.
			return true;
		}

		return $this->isExemptedProperty( $property );
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return bool
	 */
	public function isExemptedProperty( Property $property ) {
		$dataItemTypeId = DataTypeRegistry::getInstance()->getDataItemId(
			$property->findPropertyTypeID()
		);

		// Property does not belong to a valid type which means to be exempted
		if ( !$this->isValidByType( $dataItemTypeId ) ) {
			return true;
		}

		return isset( $this->propertyExemptionList[$property->getKey()] );
	}

	/**
	 * @since 2.5
	 *
	 * @param DataItem $type
	 *
	 * @return bool
	 */
	public function isValidByType( $type ): bool {
		$indexType = SMW_FT_NONE;

		if ( $type === DataItem::TYPE_BLOB ) {
			$indexType = SMW_FT_BLOB;
		}

		if ( $type === DataItem::TYPE_URI ) {
			$indexType = SMW_FT_URI;
		}

		if ( $type === DataItem::TYPE_WIKIPAGE ) {
			$indexType = SMW_FT_WIKIPAGE;
		}

		return ( $this->indexableDataTypes & $indexType ) != 0;
	}

	/**
	 * @since 2.5
	 *
	 * @param bool $enabled
	 */
	public function setEnabled( $enabled ): void {
		$this->isEnabled = (bool)$enabled;
	}

	/**
	 * @since 2.5
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return $this->isEnabled;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getTableName(): string {
		return SQLStore::FT_SEARCH_TABLE;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getIndexField(): string {
		return 'o_text';
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getSortField(): string {
		return 'o_sort';
	}

	/**
	 * @since 2.5
	 *
	 * @return int
	 */
	public function getMinTokenSize() {
		return $this->minTokenSize;
	}

	/**
	 * @since 2.5
	 *
	 * @return int $minTokenSize
	 */
	public function setMinTokenSize( $minTokenSize ): void {
		$this->minTokenSize = (int)$minTokenSize;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $token
	 *
	 * @return bool
	 */
	public function hasMinTokenLength( $token ): bool {
		return mb_strlen( $token ) >= $this->minTokenSize;
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return int
	 */
	public function getIdByProperty( Property $property ) {
		return $this->store->getObjectIds()->getId( $property->getCanonicalDiWikiPage() );
	}

	/**
	 * @since 2.5
	 *
	 * @param int $id
	 *
	 * @return WikiPage|null
	 */
	public function getDataItemById( $id ) {
		return $this->store->getObjectIds()->getDataItemById( $id );
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
