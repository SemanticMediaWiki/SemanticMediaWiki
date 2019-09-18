<?php

namespace SMW\Elastic\Indexer;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use Psr\Log\LoggerAwareTrait;
use SMW\Services\ServicesContainer;
use RuntimeException;
use SMW\DIWikiPage;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\Store;
use SMW\Utils\CharArmor;
use SMW\MediaWiki\RevisionGuard;
use SMW\MediaWiki\Collator;
use SMWDIBlob as DIBlob;
use Title;
use Revision;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Indexer {

	use MessageReporterAwareTrait;
	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ServicesContainer
	 */
	private $servicesContainer;

	/**
	 * @var FileIndexer
	 */
	private $fileIndexer;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @var boolean
	 */
	private $isRebuild = false;

	/**
	 * @var []
	 */
	private $versions = [];

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param ServicesContainer $servicesContainer
	 */
	public function __construct( Store $store, ServicesContainer $servicesContainer ) {
		$this->store = $store;
		$this->servicesContainer = $servicesContainer;
	}

	/**
	 * @since 3.0
	 *
	 * @param [] $versions
	 */
	public function setVersions( array $versions ) {
		$this->versions = $versions;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 3.0
	 *
	 * @return FileIndexer
	 */
	public function getFileIndexer() {

		if ( $this->fileIndexer === null ) {
			$this->fileIndexer = $this->servicesContainer->get( 'FileIndexer', $this );
		}

		$this->fileIndexer->setLogger(
			$this->logger
		);

		return $this->fileIndexer;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getId( DIWikiPage $dataItem ) {
		return $this->store->getObjectIds()->getId( $dataItem );
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isAccessible() {
		return $this->isSafe();
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isRebuild
	 */
	public function isRebuild( $isRebuild = true ) {
		$this->isRebuild = $isRebuild;
	}

	/**
	 * @since 3.0
	 *
	 * @return Client
	 */
	public function getConnection() {
		return $this->store->getConnection( 'elastic' );
	}

	/**
	 * @since 3.0
	 */
	public function setup() {

		$rollover = $this->servicesContainer->get(
			'Rollover',
			$this->store->getConnection( 'elastic' )
		);

		return [
			$rollover->update( ElasticClient::TYPE_DATA ),
			$rollover->update( ElasticClient::TYPE_LOOKUP )
		];
	}

	/**
	 * @since 3.0
	 */
	public function drop() {

		$rollover = $this->servicesContainer->get(
			'Rollover',
			$this->store->getConnection( 'elastic' )
		);

		return [
			$rollover->delete( ElasticClient::TYPE_DATA ),
			$rollover->delete( ElasticClient::TYPE_LOOKUP )
		];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getIndexName( $type ) {

		$index = $this->store->getConnection( 'elastic' )->getIndexNameByType(
			$type
		);

		// If the rebuilder has set a specific version, use it to avoid writing to
		// the alias of the index when running a rebuild.
		if ( isset( $this->versions[$type] ) ) {
			$index = "$index-" . $this->versions[$type];
		}

		return $index;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return Bulk
	 */
	public function newBulk( array $params ) {

		$bulk = $this->servicesContainer->get(
			'Bulk',
			$this->store->getConnection( 'elastic' )
		);

		$bulk->head( $params );

		return $bulk;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $idList
	 */
	public function delete( array $idList, $isConcept = false ) {

		if ( $idList === [] ) {
			return;
		}

		$title = Title::newFromText( $this->origin . ':' . md5( json_encode( $idList ) ) );

		$params = [
			'delete' => $idList
		];

		if ( $this->isSafe( $title, $params ) === false ) {
			return $this->pushRecoveryJob( $title, $params );
		}

		$index = $this->getIndexName(
			ElasticClient::TYPE_DATA
		);

		$params = [
			'_index' => $index,
			'_type'  => ElasticClient::TYPE_DATA
		];

		$bulk = $this->newBulk( $params );
		$time = -microtime( true );

		foreach ( $idList as $id ) {

			$bulk->delete( [ '_id' => $id ] );

			if ( $isConcept ) {
				$bulk->delete(
					[
						'_index' => $this->getIndexName( ElasticClient::TYPE_LOOKUP ),
						'_type' => ElasticClient::TYPE_LOOKUP,
						'_id' => md5( $id )
					]
				);
			}
		}

		$response = $bulk->execute();

		$this->logger->info(
			[
				'Indexer',
				'Deleted list',
				'procTime (in sec): {procTime}',
				'Response: {response}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'origin' => $this->origin,
				'procTime' => $time + microtime( true ),
				'response' => $response
			]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $dataItem
	 * @param array $data
	 */
	public function create( DIWikiPage $dataItem, array $data = [] ) {

		$title = $dataItem->getTitle();

		$params = [
			'create' => $dataItem->getHash()
		];

		if ( $this->isSafe() === false ) {
			return $this->pushRecoveryJob( $title, $params );
		}

		if ( $dataItem->getId() == 0 ) {
			$dataItem->setId( $this->getId( $dataItem ) );
		}

		if ( $dataItem->getId() == 0 ) {
			throw new RuntimeException( "Missing ID: " . $dataItem );
		}

		$connection = $this->store->getConnection( 'elastic' );

		$params = [
			'index' => $this->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'id'    => $dataItem->getId()
		];

		$data['subject'] = $this->makeSubject( $dataItem );
		$response = $connection->index( $params + [ 'body' => $data ] );

		$this->logger->info(
			[
				'Indexer',
				'Create ({subject}, {id})',
				'Response: {response}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'origin' => $this->origin,
				'subject' => $dataItem->getHash(),
				'id' => $dataItem->getId(),
				'response' => $response
			]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param ChangeDiff $changeDiff
	 * @param string $text
	 */
	public function safeReplicate( ChangeDiff $changeDiff, $text = '' ) {

		$subject = $changeDiff->getSubject();

		$params = [
			'index' => $subject->getHash()
		];

		if ( $this->isSafe() === false ) {
			return $this->pushRecoveryJob( $subject->getTitle(), $params ) ;
		}

		$this->index( $changeDiff, $text );
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage|Title|integer $id
	 *
	 * @return string
	 */
	public function fetchNativeData( $id ) {

		if ( $id instanceof DIWikiPage ) {
			$id = $id->getTitle();
		}

		if ( $id instanceof Title ) {
			$id = RevisionGuard::getLatestRevID( $id );
		}

		if ( $id == 0 ) {
			return '';
		};

		$revision = Revision::newFromId( $id );

		if ( $revision == null ) {
			return '';
		};

		$content = $revision->getContent( Revision::RAW );

		return $content->getNativeData();
	}

	/**
	 * @since 3.0
	 *
	 * @param ChangeDiff $changeDiff
	 * @param string $text
	 */
	public function index( ChangeDiff $changeDiff, $text = '' ) {

		$time = -microtime( true );
		$subject = $changeDiff->getSubject();

		$params = [
			'_index' => $this->getIndexName( ElasticClient::TYPE_DATA ),
			'_type'  => ElasticClient::TYPE_DATA
		];

		$bulk = $this->newBulk( $params );

		$this->map_data( $bulk, $changeDiff );
		$this->map_text( $bulk, $subject, $text );

		// On occasions where the change didn't contain any data but the subject
		// has been recognized to exists, create a subject body as reference
		// point
		if ( $bulk->isEmpty() ) {
			$this->map_empty( $bulk, $subject );
		}

		$response = $bulk->execute();

		// We always index (not upsert) since we want to have a complete state of
		// an entity (and ES would delete and insert any document) so trying
		// to filter and diff the data update has no real merit besides that it
		// would require us to read each ID in the update from ES and wire the data
		// back and forth which has shown to be ineffective especially when a
		// subject has many subobjects.
		//
		// The disadvantage is that we loose any auxiliary data that were attached
		// while not being part of the on-wiki information such as attachment
		// information from a file ingest.
		//
		// In order to reapply those information we could read them in the same
		// transaction before the actual update but since we expect the
		// `attachment.content` to contain a large chunk of text, we push that
		// into the job-queue so that the background process can take of it.
		//
		// Of course, this will cause a delay for the file content being searchable
		// but that should be acceptable to avoid blocking any online transaction.
		if ( !$this->isRebuild && $subject->getNamespace() === NS_FILE ) {
			$this->getFileIndexer()->pushIngestJob( $subject->getTitle() );
		}

		$this->logger->info(
			[
				'Indexer',
				'Data index completed ({subject})',
				'procTime (in sec): {procTime}',
				'Response: {response}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'origin' => $this->origin,
				'subject' => $subject->getHash(),
				'procTime' => $time + microtime( true ),
				'response' => $response
			]
		);
	}

	/**
	 * Remove anything that resembles [[:...|foo]] to avoid distracting the indexer
	 * with internal links annotation that are not relevant.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function removeLinks( $text ) {

		// [[Has foo::Bar]]
		$text = \SMW\Parser\LinksEncoder::removeAnnotation( $text );

		// {{DEFAULTSORT: ... }}
		$text = preg_replace( "/\\{\\{([^|]+?)\\}\\}/", "", $text );

		// Removed too much ...
		//	$text = preg_replace( '/\\[\\[[\s\S]+?::/', '[[', $text );

		// [[:foo|bar]]
		$text = preg_replace( '/\\[\\[:[^|]+?\\|/', '[[', $text );
		$text = preg_replace( "/\\{\\{([^|]+\\|)(.*?)\\}\\}/", "\\2", $text );
		$text = preg_replace( "/\\[\\[([^|]+?)\\]\\]/", "\\1", $text );

		return $text;
	}

	private function isSafe() {

		$connection = $this->store->getConnection( 'elastic' );

		// Make sure a node is available and is not locked by the rebuilder
		if ( !$connection->hasLock( ElasticClient::TYPE_DATA ) && $connection->ping() ) {
			return true;
		}

		return false;
	}

	private function pushRecoveryJob( $title, array $params ) {

		$indexerRecoveryJob = new IndexerRecoveryJob(
			$title,
			$params
		);

		$indexerRecoveryJob->insert();

		$this->logger->info(
			[ 'Indexer', 'Insert IndexerRecoveryJob: {subject}' ],
			[ 'method' => __METHOD__, 'role' => 'user', 'origin' => $this->origin, 'subject' => $title->getPrefixedDBKey() ]
		);
	}

	private function map_empty( $bulk, $subject ) {

		if ( $subject->getId() == 0 ) {
			$subject->setId( $this->getId( $subject ) );
		}

		$data = [];
		$data['subject'] = $this->makeSubject( $subject );

		$bulk->index(
			[
				'_id' => $subject->getId()
			],
			$data
		);
	}

	private function map_text( $bulk, $subject, $text ) {

		if ( $text === '' ) {
			return;
		}

		$id = $subject->getId();

		if ( $id == 0 ) {
			$id = $this->store->getObjectIds()->getSMWPageID(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				$subject->getSubobjectName(),
				true
			);
		}

		$bulk->upsert(
			[
				'_index' => $this->getIndexName( ElasticClient::TYPE_DATA ),
				'_type'  => ElasticClient::TYPE_DATA,
				'_id'    => $id
			],
			[
				'text_raw' => $this->removeLinks( $text )
			]
		);
	}

	private function map_data( $bulk, $changeDiff ) {

		$inserts = [];
		$inverted = [];
		$rev = $changeDiff->getAssociatedRev();
		$propertyList = $changeDiff->getPropertyList( 'id' );

		// In the event that a _SOBJ (or hereafter any inherited object)
		// is deleted, remove the reference directly from the index since
		// the object is embedded and is therefore handled outside of the
		// normal wikiPage delete action
		foreach ( $changeDiff->getTableChangeOps() as $tableChangeOp ) {
			foreach ( $tableChangeOp->getFieldChangeOps( ChangeOp::OP_DELETE ) as $fieldChangeOp ) {

				if ( !$fieldChangeOp->has( 'o_id' ) ) {
					continue;
				}

				if (
					$fieldChangeOp->has( 'p_id' ) &&
					isset( $propertyList[$fieldChangeOp->has( 'p_id' )] ) &&
					$propertyList[$fieldChangeOp->has( 'p_id' )]['_type'] === '__sob' ) {
					$bulk->delete( [ '_id' => $fieldChangeOp->get( 'o_id' ) ] );
				}
			}
		}

		foreach ( $changeDiff->getDataOps() as $tableChangeOp ) {
			foreach ( $tableChangeOp->getFieldChangeOps() as $fieldChangeOp ) {

				if ( !$fieldChangeOp->has( 's_id' ) ) {
					continue;
				}

				$this->mapRows( $fieldChangeOp, $propertyList, $inserts, $inverted, $rev );
			}
		}

		foreach ( $inverted as $id => $update ) {
			$bulk->upsert( [ '_id' => $id ], $update );
		}

		foreach ( $inserts as $id => $value ) {
			$bulk->index( [ '_id' => $id ], $value );
		}
	}

	private function mapRows( $fieldChangeOp, $propertyList, &$insertRows, &$invertedRows, $rev ) {

		// The structure to be expected in ES:
		//
		// "subject": {
		//    "title": "Foaf:knows",
		//    "subobject": "",
		//    "namespace": 102,
		//    "interwiki": "",
		//    "sortkey": "Foaf:knows"
		// },
		// "P:8": {
		//    "txtField": [
		//       "foaf knows http://xmlns.com/foaf/0.1/ Type:Page"
		//    ]
		// },
		// "P:29": {
		//    "datField": [
		//       2458150.6958333
		//    ]
		// },
		// "P:1": {
		//    "uriField": [
		//       "http://semantic-mediawiki.org/swivt/1.0#_wpg"
		//    ]
		// }

		// - datField (time value) is a numeric field (JD number) to allow using
		// ranges on dates with values being representable from January 1, 4713 BC
		// (proleptic Julian calendar)

		$sid = $fieldChangeOp->get( 's_id' );
		$connection = $this->store->getConnection( 'mw.db' );

		if ( !isset( $insertRows[$sid] ) ) {
			$insertRows[$sid] = [];
		}

		if ( !isset( $insertRows[$sid]['subject'] ) ) {
			$dataItem = $this->store->getObjectIds()->getDataItemById( $sid );

			$subject = $this->makeSubject( $dataItem );

			if ( $rev != 0 && $subject['subobject'] === '' ) {
				$subject['rev_id'] = $rev;
			}

			$insertRows[$sid]['subject'] = $subject;
		}

		// Avoid issues where the p_id is unknown as in case of an empty
		// concept (red linked) as reference
		if ( !$fieldChangeOp->has( 'p_id' ) ) {
			return;
		}

		$ins = $fieldChangeOp->getChangeOp();
		$pid = $fieldChangeOp->get( 'p_id' );

		$prop = isset( $propertyList[$pid] ) ? $propertyList[$pid] : [];

		$pid = 'P:' . $pid;
		unset( $ins['s_id'] );

		$val = 'n/a';
		$type = 'wpgField';

		if ( $fieldChangeOp->has( 'o_blob' ) && $fieldChangeOp->has( 'o_hash' ) ) {
			$type = 'txtField';
			$val = $ins['o_hash'];

			// Postgres requires special handling of blobs otherwise escaped
			// text elements are used as index input
			// Tests: P9010, Q0704, Q1206, and Q0103
			if ( $ins['o_blob'] !== null ) {
				$val = $connection->unescape_bytea( $ins['o_blob'] );
			}

			// #3020, 3035
			if ( isset( $prop['_type'] ) && $prop['_type'] === '_keyw' ) {
				$val = DIBlob::normalize( $ins['o_hash'] );
			}

			// Remove control chars and avoid Elasticsearch to throw a
			// "SmartSerializer.php: Failed to JSON encode: 5" since JSON requires
			// valid UTF-8
			$val = $this->removeLinks( mb_convert_encoding( $val, 'UTF-8', 'UTF-8' ) );
		} elseif ( $fieldChangeOp->has( 'o_serialized' ) && $fieldChangeOp->has( 'o_blob' ) ) {
			$type = 'uriField';
			$val = $ins['o_serialized'];

			if ( $ins['o_blob'] !== null ) {
				$val = $connection->unescape_bytea( $ins['o_blob'] );
			}

		} elseif ( $fieldChangeOp->has( 'o_serialized' ) && $fieldChangeOp->has( 'o_sortkey' ) ) {
			$type = strpos( $ins['o_serialized'], '/' ) !== false ? 'datField' : 'numField';
			$val = (float)$ins['o_sortkey'];
		} elseif ( $fieldChangeOp->has( 'o_value' ) ) {
			$type = 'booField';
			// Avoid a "Current token (VALUE_NUMBER_INT) not of boolean type ..."
			$val = $ins['o_value'] ? true : false;
		} elseif ( $fieldChangeOp->has( 'o_lat' ) ) {
			// https://www.elastic.co/guide/en/elasticsearch/reference/6.1/geo-point.html
			// Geo-point expressed as an array with the format: [ lon, lat ]
			// Geo-point expressed as a string with the format: "lat,lon".
			$type = 'geoField';
			$val = $ins['o_serialized'];
		} elseif ( $fieldChangeOp->has( 'o_id' ) ) {
			$type = 'wpgField';
			$dataItem = $this->store->getObjectIds()->getDataItemById( $ins['o_id'] );

			$val = $dataItem->getSortKey();
			$val = mb_convert_encoding( $val, 'UTF-8', 'UTF-8' );

			if ( !isset( $insertRows[$sid][$pid][$type] ) ) {
				$insertRows[$sid][$pid][$type] = [];
			}

			$insertRows[$sid][$pid][$type] = array_merge( $insertRows[$sid][$pid][$type], [ $val ] );
			$type = 'wpgID';
			$val = (int)$ins['o_id'];

			// Create a minimal body for an inverted relation
			//
			// When a query `[[-Has mother::Michael]]` inquiries that relationship
			// on the fact of `Michael` -> `[[Has mother::Carol]] with `Carol`
			// being redlinked (not exists as page) the query can match the object
			if ( !isset( $invertedRows[$val] ) ) {

				// Ensure we have something to sort on
				// See also Q0105#8
				$invertedRows[$val] = [ 'subject' => $this->makeSubject( $dataItem ) ];
			}

			// A null, [] (an empty array), and [null] are all equivalent, they
			// simply don't exists in an inverted index
		}

		if ( !isset( $insertRows[$sid][$pid][$type] ) ) {
			$insertRows[$sid][$pid][$type] = [];
		}

		$insertRows[$sid][$pid][$type] = array_merge(
			$insertRows[$sid][$pid][$type],
			[ $val ]
		);

		// Replicate dates in the serialized raw_format to give aggregations a chance
		// to filter dates by term
		if ( $type === 'datField' && isset( $ins['o_serialized'] ) ) {

			if ( !isset( $insertRows[$sid][$pid]["dat_raw"] ) ) {
				$insertRows[$sid][$pid]["dat_raw"] = [];
			}

			$insertRows[$sid][$pid]["dat_raw"][] = $ins['o_serialized'];
		}
	}

	private function makeSubject( DIWikiPage $subject ) {

		$title = $subject->getDBKey();

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY || $title[0] !== '_' ) {
			$title = str_replace( '_', ' ', $title );
		}

		$sort = $subject->getSortKey();
		$sort = Collator::singleton()->getSortKey( $sort );

		// Use collated sort field if available
		if ( $subject->getOption( 'sort', '' ) !== '' ) {
			$sort = $subject->getOption( 'sort' );
		}

		// This may loose some non valif UTF-8 characters as it is required by ES
		// to be strict UTF-8 otherwise the ES indexer will fail with a serialization
		// error because ES only allows UTF-8 but when the collator applies something
		// like `uca-default-u-kn` it can produce characters not valid for/by
		// ES hence the sorting compared to the SQLStore will be different (given
		// the DB stores the byte representation)
		$sort = mb_convert_encoding( $sort, 'UTF-8', 'UTF-8' );

		return [
			'title'     => $title,
			'subobject' => $subject->getSubobjectName(),
			'namespace' => $subject->getNamespace(),
			'interwiki' => $subject->getInterwiki(),
			'sortkey'   => $sort
		];
	}

}
