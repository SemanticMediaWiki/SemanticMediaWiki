<?php

namespace SMW\SPARQLStore;

use Onoi\Cache\Cache;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\Exporter\Element;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpResource;
use SMW\SemanticData;
use SMWExpData as ExpData;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class TurtleTriplesBuilder {

	/**
	 * ID used for the InMemoryPoolCache
	 */
	const POOLCACHE_ID = 'sparql.turtle.triplesbuilder';

	/**
	 * @var SemanticData
	 */
	private $semanticData = null;

	/**
	 * @var RepositoryRedirectLookup
	 */
	private $repositoryRedirectLookup = null;

	/**
	 * @var null|string
	 */
	private $triples = null;

	/**
	 * @var array
	 */
	private $prefixes = [];

	/**
	 * @var boolean
	 */
	private $hasTriplesForUpdate = false;

	/**
	 * @var integer
	 */
	private $triplesChunkSize = 80;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @since 2.0
	 *
	 * @param RepositoryRedirectLookup $repositoryRedirectLookup
	 * @param Cache|null $cache
	 */
	public function __construct( RepositoryRedirectLookup $repositoryRedirectLookup, Cache $cache = null ) {
		$this->repositoryRedirectLookup = $repositoryRedirectLookup;
		$this->cache = $cache;
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $chunkSize
	 */
	public function setTriplesChunkSize( $triplesChunkSize ) {
		$this->triplesChunkSize = (int)$triplesChunkSize;
	}

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 */
	public function doBuildTriplesFrom( SemanticData $semanticData ) {

		$this->hasTriplesForUpdate = false;
		$this->triples  = '';
		$this->prefixes = [];

		$this->doSerialize( $semanticData );
	}

	/**
	 * @since 2.0
	 *
	 * @return boolean
	 */
	public function hasTriples() {
		return $this->hasTriplesForUpdate;
	}

	/**
	 * @since 2.3
	 *
	 * @return string
	 */
	public function getTriples() {
		return $this->triples === null ? '' : $this->triples;
	}

	/**
	 * Split the triples into group of chunks as it can happen that some subjects
	 * contain SPARQL strings that exceed 1800 lines which may reach the capacity
	 * limit of a RespositoryConnector (#1110).
	 *
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getChunkedTriples() {

		$chunkedTriples = [];

		if ( $this->triples === null ) {
			return $chunkedTriples;
		}

		if ( strpos( $this->triples, " ." ) === false ) {
			return $chunkedTriples;
		}

		$triplesArrayChunks = array_chunk(
			explode( " .", $this->triples ), $this->triplesChunkSize
		);

		foreach( $triplesArrayChunks as $triplesChunk ) {
			$chunkedTriples[] = implode( " .", $triplesChunk ) . "\n";
		}

		return $chunkedTriples;
	}

	/**
	 * @since 2.0
	 *
	 * @return array
	 */
	public function getPrefixes() {
		return $this->prefixes;
	}

	/**
	 * @since 2.0
	 */
	public static function reset() {
		TurtleSerializer::reset();
	}

	private function doSerialize( SemanticData $semanticData ) {

		$expDataArray = $this->prepareUpdateExpData( $semanticData );

		if ( count( $expDataArray ) > 0 ) {

			$this->hasTriplesForUpdate = true;

			$turtleSerializer = new TurtleSerializer( true );
			$turtleSerializer->startSerialization();

			foreach ( $expDataArray as $expData ) {
				$turtleSerializer->serializeExpData( $expData );
			}

			$turtleSerializer->finishSerialization();

			$this->triples = $turtleSerializer->flushContent();
			$this->prefixes = $turtleSerializer->flushSparqlPrefixes();
		}
	}

	/**
	 * Prepare an array of SMWExpData elements that should be written to
	 * the SPARQL store. The result is empty if no updates should be done.
	 * Note that this is different from writing an SMWExpData element that
	 * has no content.
	 * Otherwise, the first SMWExpData object in the array is a translation
	 * of the given input data, but with redirects resolved. Further
	 * SMWExpData objects might be included in the resulting list to
	 * capture necessary stub declarations for objects that do not have
	 * any data in the RDF store yet.
	 *
	 * @since 1.6
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return array of SMWExpData
	 */
	private function prepareUpdateExpData( SemanticData $semanticData ) {

		$result = [];

		$expData = Exporter::getInstance()->makeExportData( $semanticData );
		$newExpData = $this->expandUpdateExpData( $expData, $result, false );
		array_unshift( $result, $newExpData );

		return $result;
	}

	/**
	 * Find a normalized representation of the given SMWExpElement that can
	 * be used in an update of the stored data. Normalization uses
	 * redirects. The type of the ExpElement might change, especially into
	 * SMWExpData in order to store auxiliary properties.
	 * Moreover, the method records any auxiliary data that should be
	 * written to the store when including this SMWExpElement into updates.
	 * This auxiliary data is collected in a call-by-ref array.
	 *
	 * @since 1.6
	 *
	 * @param Element $expElement object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 *
	 * @return ExpElement
	 */
	private function expandUpdateExpElement( Element $expElement, array &$auxiliaryExpData ) {

		if ( $expElement instanceof ExpResource ) {
			return $this->expandUpdateExpResource( $expElement, $auxiliaryExpData );
		}

		if ( $expElement instanceof ExpData ) {
			return $this->expandUpdateExpData( $expElement, $auxiliaryExpData, true );
		}

		return $expElement;
	}

	/**
	 * Find a normalized representation of the given SMWExpResource that can
	 * be used in an update of the stored data. Normalization uses
	 * redirects. The type of the ExpElement might change, especially into
	 * SMWExpData in order to store auxiliary properties.
	 * Moreover, the method records any auxiliary data that should be
	 * written to the store when including this SMWExpElement into updates.
	 * This auxiliary data is collected in a call-by-ref array.
	 *
	 * @since 1.6
	 *
	 * @param ExpResource $expResource object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 *
	 * @return ExpElement
	 */
	private function expandUpdateExpResource( ExpResource $expResource, array &$auxiliaryExpData ) {

		$exists = true;

		if ( $expResource instanceof ExpNsResource ) {
			$elementTarget = $this->repositoryRedirectLookup->findRedirectTargetResource( $expResource, $exists );
		} else {
			$elementTarget = $expResource;
		}

		if ( !$exists && $elementTarget->getDataItem() instanceof DIWikiPage && $elementTarget->getDataItem()->getDBKey() !== '' ) {

			$diWikiPage = $elementTarget->getDataItem();
			$hash = $diWikiPage->getHash();

			if ( !$this->cache->contains( $hash ) ) {
				$this->cache->save( $hash, Exporter::getInstance()->makeExportDataForSubject( $diWikiPage, true ) );
			}

			$auxiliaryExpData[$hash] = $this->cache->fetch( $hash );
		}

		return $elementTarget;
	}

	/**
	 * Find a normalized representation of the given SMWExpData that can
	 * be used in an update of the stored data. Normalization uses
	 * redirects.
	 * Moreover, the method records any auxiliary data that should be
	 * written to the store when including this SMWExpElement into updates.
	 * This auxiliary data is collected in a call-by-ref array.
	 *
	 * @since 1.6
	 * @param ExpData $expData object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 * @param $expandSubject boolean controls if redirects/auxiliary data should also be sought for subject
	 *
	 * @return ExpData
	 */
	private function expandUpdateExpData( ExpData $expData, array &$auxiliaryExpData, $expandSubject ) {

		$subjectExpResource = $expData->getSubject();

		if ( $expandSubject ) {

			$expandedExpElement = $this->expandUpdateExpElement( $subjectExpResource, $auxiliaryExpData );

			if ( $expandedExpElement instanceof ExpData ) {
				$newExpData = $expandedExpElement;
			} else { // instanceof SMWExpResource
				$newExpData = new ExpData( $subjectExpResource );
			}
		} else {
			$newExpData = new ExpData( $subjectExpResource );
		}

		foreach ( $expData->getProperties() as $propertyResource ) {

			$propertyTarget = $this->expandUpdateExpElement( $propertyResource, $auxiliaryExpData );

			foreach ( $expData->getValues( $propertyResource ) as $element ) {
				$newExpData->addPropertyObjectValue(
					$propertyTarget,
					$this->expandUpdateExpElement( $element, $auxiliaryExpData )
				);
			}
		}

		return $newExpData;
	}

}
