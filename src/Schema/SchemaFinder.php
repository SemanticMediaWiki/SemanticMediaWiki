<?php

namespace SMW\Schema;

use Onoi\Cache\Cache;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\Property\SpecificationLookup;
use SMW\Store;

/**
 * @private
 *
 * @license GPL-2.0-or-later
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

	private int $cacheTTL;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly Store $store,
		private readonly SpecificationLookup $propertySpecificationLookup,
		private readonly Cache $cache,
	) {
		$this->cacheTTL = 60 * 60 * 24 * 7;
	}

	/**
	 * @since 3.2
	 *
	 * @param PropertyChangeListener $propertyChangeListener
	 */
	public function registerPropertyChangeListener( PropertyChangeListener $propertyChangeListener ): void {
		$propertyChangeListener->addListenerCallback( new Property( '_SCHEMA_TYPE' ), [ $this, 'invalidateCache' ] );
	}

	/**
	 * @since 3.2
	 *
	 * @param Property $property
	 * @param ChangeRecord $changeRecord
	 */
	public function invalidateCache( Property $property, ChangeRecord $changeRecord ): void {
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
	 */
	public function getConstraintSchema( DataItem $dataItem ): ?SchemaList {
		return $this->newSchemaList( $dataItem, new Property( '_CONSTRAINT_SCHEMA' ) );
	}

	/**
	 * @since 3.1
	 */
	public function newSchemaList( DataItem $dataItem, Property $property ): ?SchemaList {
		if ( !$dataItem instanceof Property && !$dataItem instanceof WikiPage ) {
			return null;
		}

		$dataItems = $this->propertySpecificationLookup->getSpecification(
			$dataItem,
			$property
		);

		if ( $dataItems === null || $dataItems === false ) {
			return null;
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
	public function getSchemaListByType( $type ): SchemaList {
		$schemaList = [];
		$key = smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_LIST, $type ] );

		if ( ( $subjects = $this->cache->fetch( $key ) ) === false ) {
			$subjects = [];

			$dataItems = $this->store->getPropertySubjects(
				new Property( '_SCHEMA_TYPE' ),
				new Blob( $type )
			);

			foreach ( $dataItems as $dataItem ) {
				$subjects[] = $dataItem->getSerialization();
			}

			$this->cache->save( $key, $subjects, $this->cacheTTL );
		}

		foreach ( $subjects as $subject ) {
			$this->findSchemaDefinition( WikiPage::doUnserialize( $subject ), $schemaList );
		}

		return new SchemaList( $schemaList );
	}

	private function findSchemaDefinition( $subject, &$schemaList ): void {
		if ( !$subject instanceof WikiPage ) {
			return;
		}

		$definitions = $this->propertySpecificationLookup->getSpecification(
			$subject,
			new Property( '_SCHEMA_DEF' )
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
