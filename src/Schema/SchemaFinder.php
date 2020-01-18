<?php

namespace SMW\Schema;

use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\Store;
use SMWDIBlob as DIBlob;
use SMWDataItem as DataItem;
use Title;
use SMW\DIWikiPage;
use SMW\PropertySpecificationLookup;
use Onoi\Cache\Cache;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\Listener\ChangeListener\ChangeRecord;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaFinder {

	/**
	 * Persistent cache namespace
	 */
	const CACHE_NAMESPACE = 'smw:schema';

	/**
	 * List by types
	 */
	const TYPE_LIST = 'type/list';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var integer
	 */
	private $cacheTTL;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 * @param Cache $cache
	 */
	public function __construct( Store $store, PropertySpecificationLookup $propertySpecificationLookup, Cache $cache ) {
		$this->store = $store;
		$this->propertySpecificationLookup = $propertySpecificationLookup;
		$this->cache = $cache;
		$this->cacheTTL = 60 * 60 * 24 * 7;
	}

	/**
	 * @since 3.2
	 *
	 * @param PropertyChangeListener $propertyChangeListener
	 */
	public function registerPropertyChangeListener( PropertyChangeListener $propertyChangeListener ) {
		$propertyChangeListener->addListenerCallback( new DIProperty( '_SCHEMA_TYPE' ), [ $this, 'invalidateCache' ] );
	}

	/**
	 * @since 3.2
	 *
	 * @param DIProperty $property
	 * @param ChangeRecord $changeRecord
	 */
	public function invalidateCache( DIProperty $property, ChangeRecord $changeRecord ) {

		if ( $property->getKey() !== '_SCHEMA_TYPE' ) {
			return;
		}

		foreach ( $changeRecord as $record ) {

			if ( !$record->has( 'row.o_hash' ) ) {
				continue;
			}

			$this->cache->delete(
				smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_LIST, $record->get( 'row.o_hash' ) ] )
			);
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return SchemaList|[]
	 */
	public function getConstraintSchema( DataItem $dataItem ) {
		return $this->newSchemaList( $dataItem, new DIProperty( '_CONSTRAINT_SCHEMA' ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 * @param DIProperty $property
	 *
	 * @return SchemaList|[]
	 */
	public function newSchemaList( DataItem $dataItem, DIProperty $property ) {

		$dataItems = $this->propertySpecificationLookup->getSpecification(
			$dataItem,
			$property
		);

		if ( $dataItems === null || $dataItems === false ) {
			return [];
		}

		$schemaList = [];

		foreach ( $dataItems as $subject ) {
			$this->findSchemaDefinition( $subject, $schemaList );
		}

		return new SchemaList( $schemaList );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 *
	 * @return SchemaList
	 */
	public function getSchemaListByType( $type ) {

		$schemaList = [];
		$key = smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_LIST, $type ] );

		if ( ( $subjects = $this->cache->fetch( $key ) ) === false ) {
			$subjects = [];

			$dataItems = $this->store->getPropertySubjects(
				new DIProperty( '_SCHEMA_TYPE' ),
				new DIBlob( $type )
			);

			foreach ( $dataItems as $dataItem ) {
				$subjects[] = $dataItem->getSerialization();
			}

			$this->cache->save( $key, $subjects, $this->cacheTTL );
		}

		foreach ( $subjects as $subject ) {
			$this->findSchemaDefinition( DIWikiPage::doUnserialize( $subject ), $schemaList );
		}

		return new SchemaList( $schemaList );
	}

	private function findSchemaDefinition( $subject, &$schemaList ) {

		if ( !$subject instanceof DIWikiPage ) {
			return;
		}

		$definitions = $this->propertySpecificationLookup->getSpecification(
			$subject,
			new DIProperty( '_SCHEMA_DEF' )
		);

		$name = str_replace( '_', ' ', $subject->getDBKey() );

		foreach ( $definitions as $definition ) {
			$content = [];

			if ( $definition->getString() !== '' ) {
				$content = json_decode( $definition->getString(), true );
			}

			$schemaList[] = new SchemaDefinition(
				$name,
				$content
			);
		}
	}

}
