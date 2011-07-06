<?php

/**
 * SPARQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 *
 * @file
 * @ingroup SMWStore
 */

/**
 * Storage access class for using SMW's SPARQL database for keeping semantic
 * data.
 *
 * @since 1.6
 *
 * @note For now, the store keeps an underlying SMWSQLStore2 running for
 * completeness. This might change in the future.
 *
 * @ingroup SMWStore
 */
class SMWSparqlStore extends SMWSQLStore2 {

	public function deleteSubject( Title $subject ) {
		$dataItem = SMWDIWikiPage::newFromTitle( $subject );
		$expResource = SMWExporter::getDataItemExpElement( $dataItem, $dataItem );
		$this->deleteSparqlData( $expResource );
		parent::deleteSubject( $subject );
	}

	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		$oldWikiPage = SMWDIWikiPage::newFromTitle( $oldtitle );
		$newWikiPage = SMWDIWikiPage::newFromTitle( $newtitle );
		$oldExpResource = SMWExporter::getDataItemExpElement( $oldWikiPage, $oldWikiPage );
		$newExpResource = SMWExporter::getDataItemExpElement( $newWikiPage, $newWikiPage );
		$namespaces = array( $oldExpResource->getNamespaceId() => $oldExpResource->getNamespace() );
		$namespaces[$newExpResource->getNamespaceId()] = $newExpResource->getNamespace();
		$oldUri = SMWTurtleSerializer::getTurtleNameForExpElement( $oldExpResource );
		$newUri = SMWTurtleSerializer::getTurtleNameForExpElement( $newExpResource );

		parent::changeTitle( $oldtitle, $newtitle, $pageid, $redirid ); // do this only here, so Imported from is not moved too early

		$sparqlDatabase = smwfGetSparqlDatabase();
		$sparqlDatabase->insertDelete( "?s ?p $newUri", "?s ?p $oldUri", "?s ?p $oldUri", $namespaces );
		if ( $oldtitle->getNamespace() == SMW_NS_PROPERTY ) {
			$sparqlDatabase->insertDelete( "?s $newUri ?o", "?s $oldUri ?o", "?s $oldUri ?o", $namespaces );
		}
		// Note that we cannot change oldUri to newUri in triple subjects,
		// since some triples change due to the move. Use SMWUpdateJob.
		$newUpdate = new SMWUpdateJob( $newtitle );
		$newUpdate->run();
		if ( $redirid != 0 ) { // update/create redirect page data
			$oldUpdate = new SMWUpdateJob( $oldtitle );
			$oldUpdate->run();
		}
	}

	public function doDataUpdate( SMWSemanticData $data ) {
		parent::doDataUpdate( $data );

		$expDataArray = $this->prepareUpdateExpData( $data );

		if ( count( $expDataArray ) > 0 ) {
			$subjectResource = SMWExporter::getDataItemExpElement( $data->getSubject(), $data->getSubject() );
			$this->deleteSparqlData( $subjectResource );

			$turtleSerializer = new SMWTurtleSerializer( true );
			$turtleSerializer->startSerialization();
			foreach ( $expDataArray as $expData ) {
				$turtleSerializer->serializeExpData( $expData );
			}
			$turtleSerializer->finishSerialization();
			$triples = $turtleSerializer->flushContent();
			$prefixes = $turtleSerializer->flushSparqlPrefixes();

			smwfGetSparqlDatabase()->insertData( $triples, $prefixes );
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
	 * @param $data SMWSemanticData object containing the update data
	 * @return array of SMWExpData
	 */
	protected function prepareUpdateExpData( SMWSemanticData $data ) {
		$expData = SMWExporter::makeExportData( $data );
		$result = array();
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
	 * @param $expElement SMWExpElement object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 * @return SMWExpElement
	 */
	protected function expandUpdateExpElement( SMWExpElement $expElement, array &$auxiliaryExpData ) {
		if ( $expElement instanceof SMWExpResource ) {
			$elementTarget = $this->expandUpdateExpResource( $expElement, $auxiliaryExpData );
		} elseif ( $expElement instanceof SMWExpData ) {
			$elementTarget = $this->expandUpdateExpData( $expElement, $auxiliaryExpData, true );
		} else {
			$elementTarget = $expElement;
		}

		return $elementTarget;
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
	 * @param $expResource SMWExpResource object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 * @return SMWExpElement
	 */
	protected function expandUpdateExpResource( SMWExpResource $expResource, array &$auxiliaryExpData ) {
		$exists = true;
		if ( $expResource instanceof SMWExpNsResource ) {
			$elementTarget = $this->getSparqlRedirectTarget( $expResource, $exists );
		} else {
			$elementTarget = $expResource;
		}

		if ( !$exists && ( $elementTarget->getDataItem() instanceof SMWDIWikiPage ) ) {
			$diWikiPage = $elementTarget->getDataItem();
			$hash = $diWikiPage->getHash();
			if ( !array_key_exists( $hash, $auxiliaryExpData ) ) {
				$auxiliaryExpData[$hash] = SMWExporter::makeExportDataForSubject( $diWikiPage, null, true );
			}
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
	 * @param $expData SMWExpData object containing the update data
	 * @param $auxiliaryExpData array of SMWExpData
	 * @param $expandSubject boolean controls if redirects/auxiliary data should also be sought for subject
	 * @return SMWExpData
	 */
	protected function expandUpdateExpData( SMWExpData $expData, array &$auxiliaryExpData, $expandSubject ) {
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

	/**
	 * Find the redirect target of an SMWExpNsResource.
	 * Returns an SMWExpNsResource object the input redirects to,
	 * the input itself if there is no redirect (or it cannot be
	 * used for making a resource with a prefix).
	 *
	 * @param $expNsResource string URI to check
	 * @param $exists boolean that is set to true if $expNsResource is in the
	 * store; always false for blank nodes; always true for subobjects
	 * @return SMWExpNsResource
	 */
	protected function getSparqlRedirectTarget( SMWExpNsResource $expNsResource, &$exists ) {
		if ( $expNsResource->isBlankNode() ) {
			$exists = false;
			return $expNsResource;
		} elseif ( ( $expNsResource->getDataItem() instanceof SMWDIWikiPage ) &&
			   $expNsResource->getDataItem()->getSubobjectName() != '' ) {
			$exists = true;
			return $expNsResource;
		}

		$resourceUri = SMWTurtleSerializer::getTurtleNameForExpElement( $expNsResource );
		$rediUri = SMWTurtleSerializer::getTurtleNameForExpElement( SMWExporter::getSpecialPropertyResource( '_REDI' ) );
		$skeyUri = SMWTurtleSerializer::getTurtleNameForExpElement( SMWExporter::getSpecialPropertyResource( '_SKEY' ) );

		$sparqlResult = smwfGetSparqlDatabase()->select( '*',
		                    "$resourceUri $skeyUri ?s  OPTIONAL { $resourceUri $rediUri ?r }",
		                    array( 'LIMIT' => 1 ),
		                    array( $expNsResource->getNamespaceId() => $expNsResource->getNamespace() ) );

		$firstRow = $sparqlResult->current();
		if ( $firstRow === false ) {
			$exists = false;
			return $expNsResource;
		} elseif ( count( $firstRow ) > 1 && $firstRow[1] !== null ) {
			$exists = true;
			$rediTargetElement = $firstRow[1];
			$rediTargetUri = $rediTargetElement->getUri();
			$wikiNamespace = SMWExporter::getNamespaceUri( 'wiki' );
			if ( strpos( $rediTargetUri, $wikiNamespace ) === 0 ) {
				return new SMWExpNsResource( substr( $rediTargetUri, 0, strlen( $wikiNamespace ) ) , $wikiNamespace, 'wiki' );
			} else {
				return $expNsResource;
			}
		} else {
			$exists = true;
			return $expNsResource;
		}
	}

	/**
	 * Delete from the SPARQL database all data that is associated with the
	 * given resource.
	 *
	 * @param $expResource SMWExpResource
	 * @return boolean success
	 */
	protected function deleteSparqlData( SMWExpResource $expResource ) {
		$resourceUri = SMWTurtleSerializer::getTurtleNameForExpElement( $expResource );
		$masterPageProperty = SMWExporter::getSpecialNsResource( 'swivt', 'masterPage' );
		$masterPagePropertyUri = SMWTurtleSerializer::getTurtleNameForExpElement( $masterPageProperty );

		$success = smwfGetSparqlDatabase()->deleteContentByValue( $masterPagePropertyUri, $resourceUri );
		if ( $success ) {
			return smwfGetSparqlDatabase()->delete( "$resourceUri ?p ?o", "$resourceUri ?p ?o"  );
		} else {
			return false;
		}
	}


	public function getQueryResult( SMWQuery $query ) {
		global $smwgIgnoreQueryErrors, $smwgQSortingSupport;

		if ( !$smwgIgnoreQueryErrors &&
		     ( $query->querymode != SMWQuery::MODE_DEBUG ) &&
		     ( count( $query->getErrors() ) > 0 ) ) {
			return new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this, false );
			// NOTE: we check this here to prevent unnecessary work, but we may need to check it after query processing below again in case more errors occurred
		}

		if ( $query->querymode == SMWQuery::MODE_NONE ) { // don't query, but return something to printer
			return new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this, true );
		} elseif ( $query->querymode == SMWQuery::MODE_DEBUG ) {
			$queryEngine = new SMWSparqlStoreQueryEngine( $this );
			return $queryEngine->getDebugQueryResult( $query ); 
		} elseif ( $query->querymode == SMWQuery::MODE_COUNT ) {
			$queryEngine = new SMWSparqlStoreQueryEngine( $this );
			return $queryEngine->getCountQueryResult( $query ); 
		} else {
			$queryEngine = new SMWSparqlStoreQueryEngine( $this );
			return $queryEngine->getInstanceQueryResult( $query ); 
		}
	}

	public function drop( $verbose = true ) {
		parent::drop( $verbose );
		smwfGetSparqlDatabase()->delete( "?s ?p ?o", "?s ?p ?o" );
	}

}

