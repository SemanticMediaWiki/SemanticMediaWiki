<?php

namespace SMW\SQLStore\Lookup;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMW\Message;
use SMW\DataValueFactory;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIBlob as DIBlob;
use SMWDIContainer as DIContainer;
use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MonolingualTextLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $caller = '';

	/**
	 * @var []
	 */
	private static $lookupCache = [];

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 */
	public function clearLookupCache() {
		self::$lookupCache = [];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $caller
	 */
	public function setCaller( $caller ) {
		$this->caller = $caller;
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return DIContainer|null
	 */
	public function newDIContainer( DIWikiPage $subject, DIProperty $property, $languageCode = null ) {

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

			return new DIContainer( $containerSemanticData );
		}

		$hash = $subject->getHash();

		if ( isset( self::$lookupCache[$hash] ) ) {
			return new DIContainer( self::$lookupCache[$hash] );
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
			if ( $subject->getNamespace() === SMW_NS_PROPERTY && ( $dbKey = $subject->getDBKey() ) && $dbKey[0] === '_' ) {
				$subject = DIProperty::newFromUserLabel( $dbKey )->getCanonicalDIWikiPage(
					$subobjectName
				);
			}

			$h = $subject->getHash();
			$text = $row->text_short;

			if ( $row->text_long !== null ) {
				$text = $connection->unescape_bytea( $row->text_long );
			}

			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_TEXT' ),
				new DIBlob( $text )
			);

			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_LCODE' ),
				new DIBlob( $row->lcode )
			);

			self::$lookupCache[$h] = $containerSemanticData;
		}

		if ( isset( self::$lookupCache[$hash] ) ) {
			$container = new DIContainer( self::$lookupCache[$hash] );
		}

		return $container;
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return []
	 */
	public function newDataValue( DIWikiPage $subject, DIProperty $property, $languageCode = null ) {

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
				new DIProperty( '_TEXT' ),
				new DIBlob( $text )
			);

			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_LCODE' ),
				new DIBlob( $row->lcode )
			);

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				$subject,
				$property
			);

			$dataValue->setDataItem(
				new DIContainer( $containerSemanticData )
			);
		}

		return $dataValue;
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return []
	 */
	public function fetchFromTable( DIWikiPage $subject, DIProperty $property, $languageCode = null ) {

		/**
		 * This method avoids access to `Store::getSemanticData` in order to
		 * optimize the query and produce something like:
		 *
		 */

		$subobjectName = $subject->getSubobjectName();

		$sid = $this->store->getObjectIds()->getSMWPageID(
			$subject->getDBKey(),
			$subject->getNamespace(),
			$subject->getInterWiki(),
			$subobjectName
		);

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );

		$propTable = $this->getPropertyTable(
			$property
		);

		$query->table( $propTable->getName(), 't0' );

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
		if ( substr( $subobjectName, 0, 3 ) === '_ML' ) {
			$query->condition( $query->eq( "t0.o_id", $sid ) );
		} else {
			$query->condition( $query->eq( "t0.s_id", $sid ) );
		}

		if ( !$propTable->isFixedPropertyTable() ) {

			$pid = $this->store->getObjectIds()->getSMWPropertyID(
				$property
			);

			$query->condition( $query->eq( "t0.p_id", $pid ) );

			$query->join(
				'INNER JOIN',
				[ SQLStore::ID_TABLE => 't1 ON t0.p_id=t1.smw_id' ]
			);
		}

		$query->condition( $query->neq( "o0.smw_iw", SMW_SQL3_SMWIW_OUTDATED ) );
		$query->condition( $query->neq( "o0.smw_iw", SMW_SQL3_SMWDELETEIW ) );

		$query->join(
			'INNER JOIN',
			[ SQLStore::ID_TABLE => 'o0 ON t0.o_id=o0.smw_id' ]
		);

		$text = new DIProperty( '_TEXT' );

		$text_table = $this->getPropertyTable(
			$text
		);

		$query->join(
			'INNER JOIN',
			[ $text_table->getName() => 't2 ON t2.s_id=o0.smw_id' ]
		);

		$lcode = new DIProperty( '_LCODE' );

		$lcode_table = $this->getPropertyTable(
			$lcode
		);

		$query->join(
			'INNER JOIN',
			[ $lcode_table->getName() => 't3 ON t3.s_id=o0.smw_id' ]
		);

		if ( $languageCode !== null ) {
			$query->condition( $query->eq( "t3.o_hash", $languageCode ) );
		}

		$query->field( 't0.o_id', 'id' );
		$query->field( 'o0.smw_title', 'v0' );
		$query->field( 'o0.smw_namespace', 'v1' );
		$query->field( 'o0.smw_iw', 'v2' );
		$query->field( 'o0.smw_subobject', 'v3' );
		$query->field( 't2.o_hash', 'text_short' );
		$query->field( 't2.o_blob', 'text_long' );
		$query->field( 't3.o_hash', 'lcode' );

		$caller = __METHOD__;

		if ( strval( $this->caller ) !== '' ) {
			$caller .= " (for " . $this->caller . ")";
		}

		return $query->execute( $caller );
	}

	private function getPropertyTable( DIProperty $property ) {

		$propTableId = $this->store->findPropertyTableID(
			$property
		);

		$propTables = $this->store->getPropertyTables();

		if ( !isset( $propTables[$propTableId] ) ) {
			return [];
		}

		return $propTables[$propTableId];
	}

	private function newContainerSemanticData( $row ) {

		if ( $row instanceof DIWikiPage ) {
			$subject = $row;
		} else {
			$subject = new DIWikiPage(
				$row->v0,
				$row->v1,
				$row->v2,
				$row->v3
			);
		}

		return new ContainerSemanticData( $subject );
	}

}
