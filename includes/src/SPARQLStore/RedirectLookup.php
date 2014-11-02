<?php

namespace SMW\SPARQLStore;

use SMW\Cache\Cache;
use SMW\Cache\FixedInMemoryCache;

use SMW\DIWikiPage;

use SMWSparqlDatabase as SparqlDatabase;
use SMWExpNsResource as ExpNsResource;
use SMWExpResource as ExpResource;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

use RuntimeException;

/**
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
	private $connection = null;

	/**
	 * @var Cache|null
	 */
	private static $resourceUriTargetCache = null;

	/**
	 * @since 2.0
	 *
	 * @param SparqlDatabase $connection
	 * @param Cache|null $cache
	 */
	public function __construct( SparqlDatabase $connection, Cache $cache = null ) {
		$this->connection = $connection;

		if ( $cache !== null ) {
			self::$resourceUriTargetCache = $cache;
		}
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		self::$resourceUriTargetCache = null;
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

		if ( $expNsResource->isBlankNode() || $this->isNonRedirectableResource( $expNsResource ) ) {
			$exists = false;
			return $expNsResource;
		}

		if ( ( $expNsResource->getDataItem() instanceof DIWikiPage ) &&
			   $expNsResource->getDataItem()->getSubobjectName() !== '' ) {
			return $expNsResource;
		}

		if ( !$this->getCache()->contains( $expNsResource->getUri() ) ) {
			$this->getCache()->save( $expNsResource->getUri(), $this->lookupResourceUriTargetFromDatabase( $expNsResource ) );
		}

		$firstRow = $this->getCache()->fetch( $expNsResource->getUri() );

		if ( $firstRow === false ) {
			$exists = false;
			return $expNsResource;
		}

		if ( count( $firstRow ) > 1 && !is_null( $firstRow[1] ) ) {
			return $this->getResourceForTargetElement( $expNsResource, $firstRow[1] );
		}

		return $expNsResource;
	}

	private function isNonRedirectableResource( ExpNsResource $expNsResource ) {
		return $expNsResource->getNamespaceId() === 'swivt' ||
			$expNsResource->getNamespaceId() === 'rdf' ||
			$expNsResource->getNamespaceId() === 'rdfs' ||
			( $expNsResource->getNamespaceId() === 'property' && strrpos( $expNsResource->getLocalName(), 'aux' ) );
	}

	private function lookupResourceUriTargetFromDatabase( ExpNsResource $expNsResource ) {

		$resourceUri = TurtleSerializer::getTurtleNameForExpElement( $expNsResource );
		$rediUri = TurtleSerializer::getTurtleNameForExpElement( Exporter::getSpecialPropertyResource( '_REDI' ) );
		$skeyUri = TurtleSerializer::getTurtleNameForExpElement( Exporter::getSpecialPropertyResource( '_SKEY' ) );

		$federateResultSet = $this->connection->select(
			'*',
			"$resourceUri $skeyUri ?s  OPTIONAL { $resourceUri $rediUri ?r }",
			array( 'LIMIT' => 1 ),
			array( $expNsResource->getNamespaceId() => $expNsResource->getNamespace() )
		);

		return $federateResultSet->current();
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

	private function getCache() {

		if ( self::$resourceUriTargetCache === null ) {
			self::$resourceUriTargetCache = new FixedInMemoryCache( 500 );
		}

		return self::$resourceUriTargetCache;
	}

}
