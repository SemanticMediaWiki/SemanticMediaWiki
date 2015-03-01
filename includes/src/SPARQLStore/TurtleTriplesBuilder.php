<?php

namespace SMW\SPARQLStore;

use SMW\SemanticData;
use SMW\DIWikiPage;

use SMWTurtleSerializer as TurtleSerializer;
use SMWExporter;
use SMWExpElement;
use SMWExpData;
use SMWExpResource;
use SMWExpNsResource;

/**
 * @ingroup Sparql
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class TurtleTriplesBuilder {

	/**
	 * @var SemanticData
	 */
	private $semanticData = null;

	/**
	 * @var RedirectLookup
	 */
	private $redirectLookup = null;

	/**
	 * @var null|string
	 */
	private $triples = null;

	/**
	 * @var null|array
	 */
	private $prefixes = null;

	/**
	 * @var null|boolean
	 */
	private $hasTriplesForUpdate = null;

	/**
	 * @var array
	 */
	private static $dataItemExportCache = array();

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param RedirectLookup $redirectLookup
	 */
	public function __construct( SemanticData $semanticData, RedirectLookup $redirectLookup ) {
		$this->semanticData = $semanticData;
		$this->redirectLookup = $redirectLookup;
	}

	/**
	 * @since 2.0
	 *
	 * @return TurtleTriplesBuilder
	 */
	public function doBuild() {
		return $this->serializeToTurtleRepresentation();
	}

	/**
	 * @since 2.0
	 *
	 * @return string
	 */
	public function getTriples() {

		if ( $this->triples === null ) {
			$this->doBuild();
		}

		return $this->triples;
	}

	/**
	 * @since 2.0
	 *
	 * @return array
	 */
	public function getPrefixes() {

		if ( $this->prefixes === null ) {
			$this->doBuild();
		}

		return $this->prefixes;
	}

	/**
	 * @since 2.0
	 *
	 * @return boolean
	 */
	public function hasTriplesForUpdate() {

		if ( $this->hasTriplesForUpdate === null ) {
			$this->doBuild();
		}

		return $this->hasTriplesForUpdate;
	}

	private function serializeToTurtleRepresentation() {

		$this->hasTriplesForUpdate = false;
		$this->triples  = '';
		$this->prefixes = array();

		$expDataArray = $this->prepareUpdateExpData( $this->semanticData );

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

		return $this;
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

		$result = array();

		$expData = SMWExporter::makeExportData( $semanticData );
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
	 * @param $expElement SMWExpElement object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 *
	 * @return SMWExpElement
	 */
	private function expandUpdateExpElement( SMWExpElement $expElement, array &$auxiliaryExpData ) {

		if ( $expElement instanceof SMWExpResource ) {
			return $this->expandUpdateExpResource( $expElement, $auxiliaryExpData );
		}

		if ( $expElement instanceof SMWExpData ) {
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
	 * @param $expResource SMWExpResource object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 *
	 * @return SMWExpElement
	 */
	private function expandUpdateExpResource( SMWExpResource $expResource, array &$auxiliaryExpData ) {

		$exists = true;

		if ( $expResource instanceof SMWExpNsResource ) {
			$elementTarget = $this->redirectLookup->findRedirectTargetResource( $expResource, $exists );
		} else {
			$elementTarget = $expResource;
		}

		if ( !$exists && ( $elementTarget->getDataItem() instanceof DIWikiPage ) ) {

			$diWikiPage = $elementTarget->getDataItem();
			$hash = $diWikiPage->getHash();

			if ( !isset( self::$dataItemExportCache[ $hash ] ) ) {
				self::$dataItemExportCache[ $hash ] = SMWExporter::getInstance()->makeExportDataForSubject( $diWikiPage, true );
			}

			$auxiliaryExpData[ $hash ] = self::$dataItemExportCache[ $hash ];
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
	 * @param $expData SMWExpData object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 * @param $expandSubject boolean controls if redirects/auxiliary data should also be sought for subject
	 * @return SMWExpData
	 */
	private function expandUpdateExpData( SMWExpData $expData, array &$auxiliaryExpData, $expandSubject ) {

		$subjectExpResource = $expData->getSubject();

		if ( $expandSubject ) {

			$expandedExpElement = $this->expandUpdateExpElement( $subjectExpResource, $auxiliaryExpData );

			if ( $expandedExpElement instanceof SMWExpData ) {
				$newExpData = $expandedExpElement;
			} else { // instanceof SMWExpResource
				$newExpData = new SMWExpData( $subjectExpResource );
			}
		} else {
			$newExpData = new SMWExpData( $subjectExpResource );
		}

		foreach ( $expData->getProperties() as $propertyResource ) {

			$propertyTarget = $this->expandUpdateExpElement( $propertyResource, $auxiliaryExpData );

			foreach ( $expData->getValues( $propertyResource ) as $expElement ) {
				$elementTarget = $this->expandUpdateExpElement( $expElement, $auxiliaryExpData );
				$newExpData->addPropertyObjectValue( $propertyTarget, $elementTarget );
			}
		}

		return $newExpData;
	}

}
