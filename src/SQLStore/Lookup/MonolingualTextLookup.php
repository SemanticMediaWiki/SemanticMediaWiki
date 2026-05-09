<?php

namespace SMW\SQLStore\Lookup;

use InvalidArgumentException;
use RuntimeException;
use SMW\DataItems\Blob;
use SMW\DataItems\Container;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class MonolingualTextLookup {

	/**
	 * @var string
	 */
	private $caller = '';

	/**
	 * @var array
	 */
	private static array $lookupCache = [];

	/**
	 * @since 3.1
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.1
	 */
	public function clearLookupCache(): void {
		self::$lookupCache = [];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $caller
	 */
	public function setCaller( $caller ): void {
		$this->caller = $caller;
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $subject
	 *
	 * @return Container|null
	 */
	public function newDIContainer( WikiPage $subject, Property $property, $languageCode = null ): ?Container {
		if ( $subject->getSubobjectName() !== '' && $languageCode !== null ) {
			throw new InvalidArgumentException( "Expected for a container reference no language code." );
		}

		// Missing a container reference!
		if ( $subject->getSubobjectName() === '' ) {
			return null;
		}

		if ( $property->isInverse() ) {
			$containerSemanticData = $this->newContainerSemanticData( $subject );

			$containerSemanticData->copyDataFrom(
				$this->store->getSemanticData( $subject )
			);

			return new Container( $containerSemanticData );
		}

		$hash = $subject->getHash();

		if ( isset( self::$lookupCache[$hash] ) ) {
			return new Container( self::$lookupCache[$hash] );
		}

		$res = $this->fetchFromTable( $subject, $property, $languageCode );
		$container = null;

		$connection = $this->store->getConnection( 'mw.db' );

		foreach ( $res as $row ) {

			$containerSemanticData = $this->newContainerSemanticData(
				$row
			);

			$subject = $containerSemanticData->getSubject();
			$subobjectName = $subject->getSubobjectName();

			// Handle predefined properties
			if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
				$dbKey = $subject->getDBKey();
				if ( $dbKey && $dbKey[0] === '_' ) {
					$subject = Property::newFromUserLabel( $dbKey )->getCanonicalDIWikiPage(
						$subobjectName
					);
				}
			}

			$h = $subject->getHash();
			$text = $row->text_short;

			if ( $row->text_long !== null ) {
				$text = $connection->unescape_bytea( $row->text_long );
			}

			$containerSemanticData->addPropertyObjectValue(
				new Property( '_TEXT' ),
				new Blob( $text )
			);

			$containerSemanticData->addPropertyObjectValue(
				new Property( '_LCODE' ),
				new Blob( $row->lcode )
			);

			self::$lookupCache[$h] = $containerSemanticData;
		}

		if ( isset( self::$lookupCache[$hash] ) ) {
			$container = new Container( self::$lookupCache[$hash] );
		}

		return $container;
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $subject
	 *
	 * @return DataValue|null
	 */
	public function newDataValue( WikiPage $subject, Property $property, $languageCode = null ): ?DataValue {
		$res = $this->fetchFromTable( $subject, $property, $languageCode );
		$dataValue = null;

		$connection = $this->store->getConnection( 'mw.db' );

		foreach ( $res as $row ) {

			$containerSemanticData = $this->newContainerSemanticData(
				$row
			);

			$text = $row->text_short;

			if ( $row->text_long !== null ) {
				$text = $connection->unescape_bytea( $row->text_long );
			}

			$containerSemanticData->addPropertyObjectValue(
				new Property( '_TEXT' ),
				new Blob( $text )
			);

			$containerSemanticData->addPropertyObjectValue(
				new Property( '_LCODE' ),
				new Blob( $row->lcode )
			);

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				$subject,
				$property
			);

			$dataValue->setDataItem(
				new Container( $containerSemanticData )
			);
		}

		return $dataValue;
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $subject
	 *
	 * @return iterable
	 */
	public function fetchFromTable( WikiPage $subject, Property $property, $languageCode = null ) {
		/**
		 * This method avoids access to `Store::getSemanticData` in order to
		 * optimize the query and produce something like:
		 *
		 */
		$connection = $this->store->getConnection( 'mw.db' );

		$propTable = $this->getPropertyTable(
			$property
		);

		/**
		 * In case of a _ML... reference use the o_id (object) field
		 *
		 * SELECT
		 *  t0.o_id AS id,
		 *  o0.smw_title AS v0,
		 *  o0.smw_namespace AS v1,
		 *  o0.smw_iw AS v2, o0.smw_subobject AS v3,
		 *  t2.o_hash AS text_short,
		 *  t2.o_blob AS text_long,
		 *  t3.o_hash AS lcode
		 * FROM `smw_di_wikipage` AS t0
		 * INNER JOIN `smw_object_ids` AS t1 ON t0.p_id=t1.smw_id
		 * INNER JOIN `smw_object_ids` AS o0 ON t0.o_id=o0.smw_id
		 * INNER JOIN `smw_fpt_text` AS t2 ON t2.s_id=o0.smw_id
		 * INNER JOIN `smw_fpt_lcode` AS t3 ON t3.s_id=o0.smw_id
		 * WHERE
		 *  (t0.o_id='364192') AND
		 *  (t0.p_id='195233') AND
		 *  (o0.smw_iw!=':smw') AND
		 *  (o0.smw_iw!=':smw-delete')
		 */

		// Account for special properties
		if ( $subject->inNamespace( SMW_NS_PROPERTY ) ) {
			$prop = Property::newFromUserLabel( $subject->getDBKey() );

			$subject = new WikiPage(
				$prop->getKey(),
				$subject->getNamespace(),
				$subject->getInterWiki(),
				$subject->getSubobjectName()
			);
		}

		$qb = $connection->newSelectQueryBuilder()
			->from( $propTable->getName(), 't0' )
			->join( SQLStore::ID_TABLE, 'o0', 't0.o_id=o0.smw_id' );

		// Is it a Monolingual representation?
		if ( $subject->isSubEntityOf( SMW_SUBENTITY_MONOLINGUAL ) ) {
			$qb->where( [ 'o0.smw_hash' => $subject->getSha1() ] );
		} else {
			// We don't have a _ML entity reference hence we add a JOIN to find
			// such entity
			$qb->where( [ 'o1.smw_hash' => $subject->getSha1() ] );

			$qb->join( SQLStore::ID_TABLE, 'o1', 't0.s_id=o1.smw_id' );
		}

		$qb->andWhere( $connection->expr( 'o0.smw_iw', '!=', SMW_SQL3_SMWIW_OUTDATED ) );
		$qb->andWhere( $connection->expr( 'o0.smw_iw', '!=', SMW_SQL3_SMWDELETEIW ) );

		if ( !$propTable->isFixedPropertyTable() ) {

			$pid = $this->store->getObjectIds()->getSMWPropertyID(
				$property
			);

			$qb->andWhere( [ 't0.p_id' => $pid ] );

			$qb->join( SQLStore::ID_TABLE, 't1', 't0.p_id=t1.smw_id' );
		}

		$text = new Property( '_TEXT' );

		$text_table = $this->getPropertyTable(
			$text
		);

		$qb->join( $text_table->getName(), 't2', 't2.s_id=o0.smw_id' );

		$lcode = new Property( '_LCODE' );

		$lcode_table = $this->getPropertyTable(
			$lcode
		);

		$qb->join( $lcode_table->getName(), 't3', 't3.s_id=o0.smw_id' );

		if ( $languageCode !== null ) {
			$qb->andWhere( [ 't3.o_hash' => $languageCode ] );
		}

		$qb->select( [
			'id' => 't0.o_id',
			'v0' => 'o0.smw_title',
			'v1' => 'o0.smw_namespace',
			'v2' => 'o0.smw_iw',
			'v3' => 'o0.smw_subobject',
			'text_short' => 't2.o_hash',
			'text_long' => 't2.o_blob',
			'lcode' => 't3.o_hash',
		] );

		$caller = __METHOD__;

		if ( strval( $this->caller ) !== '' ) {
			$caller .= " (for " . $this->caller . ")";
		}

		return $qb->caller( $caller )->fetchResultSet();
	}

	/**
	 * @return PropertyTableDefinition
	 * @throws RuntimeException
	 */
	private function getPropertyTable( Property $property ) {
		$propTableId = $this->store->findPropertyTableID(
			$property
		);

		$propTables = $this->store->getPropertyTables();

		if ( !isset( $propTables[$propTableId] ) ) {
			throw new RuntimeException( "Unknown property table for ID $propTableId" );
		}

		return $propTables[$propTableId];
	}

	private function newContainerSemanticData( $row ): ContainerSemanticData {
		if ( $row instanceof WikiPage ) {
			$subject = $row;
		} else {
			$subject = new WikiPage(
				$row->v0,
				$row->v1,
				$row->v2,
				$row->v3
			);
		}

		return new ContainerSemanticData( $subject );
	}

}
