<?php

namespace SMW\SPARQLStore;

use SMW\DIWikiPage;

use SMWSparqlDatabase as SparqlDatabase;
use SMWExpNsResource as ExpNsResource;
use SMWExpResource as ExpResource;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

use RuntimeException;

/**
 * @ingroup Sparql
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class RedirectLookup {

	/**
	 * @var SMWSparqlDatabase
	 */
	private $sparqlDatabase = null;

	/**
	 * @since 2.0
	 *
	 * @param SparqlDatabase $sparqlDatabase
	 */
	public function __construct( SparqlDatabase $sparqlDatabase ) {
		$this->sparqlDatabase = $sparqlDatabase;
	}

	/**
	 * Find the redirect target of an ExpNsResource
	 *
	 * Returns an SMWExpNsResource object the input redirects to, the input
	 * itself if there is no redirect (or it cannot be used for making a resource
	 * with a prefix).
	 *
	 * @since 1.6
	 *
	 * @param ExpNsResource $expNsResource string URI to check
	 * @param boolean $existsthat is set to true if $expNsResource is in the
	 * store; always false for blank nodes; always true for subobjects
	 *
	 * @return ExpNsResource
	 * @throws RuntimeException
	 */
	public function findRedirectTargetResource( ExpNsResource $expNsResource, &$exists ) {

		$exists = true;

		if ( $expNsResource->isBlankNode() ) {
			$exists = false;
			return $expNsResource;
		}

		if ( ( $expNsResource->getDataItem() instanceof DIWikiPage ) &&
			   $expNsResource->getDataItem()->getSubobjectName() !== '' ) {
			return $expNsResource;
		}

		$firstRow = $this->lookupResourceUriTargetFromDatabase( $expNsResource );

		if ( $firstRow === false ) {
			$exists = false;
			return $expNsResource;
		}

		if ( count( $firstRow ) > 1 && !is_null( $firstRow[1] ) ) {
			return $this->getResourceForTargetElement( $expNsResource, $firstRow[1] );
		}

		return $expNsResource;
	}

	private function lookupResourceUriTargetFromDatabase( ExpNsResource $expNsResource ) {

		$resourceUri = TurtleSerializer::getTurtleNameForExpElement( $expNsResource );
		$rediUri = TurtleSerializer::getTurtleNameForExpElement( Exporter::getSpecialPropertyResource( '_REDI' ) );
		$skeyUri = TurtleSerializer::getTurtleNameForExpElement( Exporter::getSpecialPropertyResource( '_SKEY' ) );

		$federateResultList = $this->sparqlDatabase->select(
			'*',
			"$resourceUri $skeyUri ?s  OPTIONAL { $resourceUri $rediUri ?r }",
			array( 'LIMIT' => 1 ),
			array( $expNsResource->getNamespaceId() => $expNsResource->getNamespace() )
		);

		return $federateResultList->current();
	}

	private function getResourceForTargetElement( ExpNsResource $expNsResource, $rediTargetElement ) {

		if ( !$rediTargetElement instanceof ExpResource ) {
			throw new RuntimeException( 'Expected a ExpResource instance' );
		}

		$rediTargetUri = $rediTargetElement->getUri();
		$wikiNamespace = Exporter::getNamespaceUri( 'wiki' );

		if ( strpos( $rediTargetUri, $wikiNamespace ) === 0 ) {
			return new ExpNsResource( substr( $rediTargetUri, strlen( $wikiNamespace ) ), $wikiNamespace, 'wiki' );
		}

		return $expNsResource;
	}

}
