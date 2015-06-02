<?php

namespace SMW\SQLStore;

use Onoi\Cache\Cache;
use Onoi\BlobStore\BlobStore;
use SMW\Store;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\HashBuilder;
use SMW\CircularReferenceGuard;
use SMWDataItem as DataItem;
use SMWRequestOptions as RequestOptions;
use RuntimeException;

/**
 * Intermediary (fast) access to serialized blob values to avoid DB access on
 * objects that are static until it is altered. An intermediary object will
 * invalidate itself on any delete, update, change, or move operation.
 *
 * Each subject (including its subobjects) will be stored as individual blob with
 * each operation belonging to that subject extending its blob to be able
 * to discard the entire entity at once.
 *
 * Each operation request will either fill the cache or return the result from
 * the cache until the subject is changed and the whole container is being
 * flushed.
 *
 * The class could be decorator but due to the nature of the current Store design
 * it is called from within each method. The class is not for public use and it
 * is expected to be accessed only by a Store operation.
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ByBlobStoreIntermediaryValueLookup {

	/**
	 * Update this version number when the serialization format
	 * changes.
	 */
	const VERSION = '1.0';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var BlobStore
	 */
	private $blobStore;

	/**
	 * @since 2.3
	 *
	 * @param Store $store
	 * @param BlobStore $blobStore
	 */
	public function __construct( Store $store, BlobStore $blobStore ) {
		$this->store = $store;
		$this->blobStore = $blobStore;
	}

	/**
	 * @since 2.3
	 *
	 * @param CircularReferenceGuard $circularReferenceGuard
	 */
	public function setCircularReferenceGuard( CircularReferenceGuard $circularReferenceGuard ) {
		$this->circularReferenceGuard = $circularReferenceGuard;
	}

	/**
	 * @see Store::getSemanticData
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject
	 * @param RequestOptions $requestOptions = null
	 *
	 * @return array
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false ) {

		if ( !$this->blobStore->canUse() ) {
			return $this->store->getReader()->getSemanticData( $subject, $filter );
		}

		$container = $this->blobStore->read(
			$this->getKeyForMainSubject( $subject )
		);

		$sdid = HashBuilder::createHashIdForContent(
			array(
				$subject->getSubobjectName(),
				(array)$filter,
				self::VERSION
			),
			'sd:'
		);

		if ( $container->has( $sdid ) ) {
			return $container->get( $sdid );
		}

		$semanticData = $this->store->getReader()->getSemanticData(
			$subject,
			$filter
		);

		$semanticData->setLastModified( wfTimestamp( TS_UNIX ) );

		$container->set( $sdid, $semanticData );

		$this->blobStore->save(
			$container
		);

		return $semanticData;
	}

	/**
	 * @see Store::getProperties
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject
	 * @param RequestOptions $requestOptions = null
	 *
	 * @return array
	 */
	public function getProperties( DIWikiPage $subject, $requestOptions = null ) {

		$container = array();

		if ( !$this->blobStore->canUse() ) {
			return $this->store->getReader()->getProperties( $subject, $requestOptions );
		}

		$container = $this->blobStore->read(
			$this->getKeyForMainSubject( $subject )
		);

		$plid = HashBuilder::createHashIdForContent(
			array(
				$subject->getSubobjectName(),
				(array)$requestOptions,
				self::VERSION
			),
			'pl:'
		);

		if ( $container->has( $plid ) ) {
			return $this->resolveRedirectTargets( $container->get( $plid ) );
		}

		$result = $this->store->getReader()->getProperties(
			$subject,
			$requestOptions
		);

		$container->set( $plid, $result );

		$this->blobStore->save(
			$container
		);

		return $result;
	}

	/**
	 * @see Store::getPropertyValues
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage|null $subject
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions = null
	 *
	 * @return array
	 */
	public function getPropertyValues( $subject = null, DIProperty $property, $requestOptions = null ) {

		$container = array();

		// The cache is not used for $subject === null (means all values for
		// the given property are returned)
		if ( $subject === null || !$this->blobStore->canUse() ) {
			return $this->store->getReader()->getPropertyValues( $subject, $property, $requestOptions );
		}

		$container = $this->blobStore->read(
			$this->getKeyForMainSubject( $subject )
		);

		$pvid = HashBuilder::createHashIdForContent(
			array(
				$property->getKey(),
				$subject->getSubobjectName(),
				(array)$requestOptions,
				self::VERSION
			),
			'pv:'
		);

		if ( $container->has( $pvid ) ) {
			return $this->resolveRedirectTargets( $container->get( $pvid ) );
		}

		$result = $this->store->getReader()->getPropertyValues(
			$subject,
			$property,
			$requestOptions
		);

		$container->set( $pvid, $result );

		$this->blobStore->save(
			$container
		);

		return $result;
	}

	/**
	 * Remove a cache item that appears during an alteration action (update,
	 * change, delete) to ensure that we always have the correct set of matches.
	 * Trying to do any complex invalidation process seems unproductive therefore
	 * the whole object is scrapped.
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject
	 */
	public function deleteFor( DIWikiPage $subject ) {
		$this->blobStore->delete( $this->getKeyForMainSubject( $subject ) );
	}

	/**
	 * Ensures that new redirects are resolved while a value is still kept
	 * in cache (internally it uses getPropertyValues hence we don't loose
	 * much as objects are reused during the lookup).
	 */
	private function resolveRedirectTargets( array $results ) {

		$dataItems =  array();

		foreach ( $results as $dataItem ) {
			$dataItems[] = $this->findRedirectTarget( $dataItem );
		}

		return $dataItems;
	}

	private function findRedirectTarget( $dataItem ) {

		if ( $this->circularReferenceGuard === null || ( !$dataItem instanceof DIWikiPage && !$dataItem instanceof DIProperty ) ) {
			return $dataItem;
		}

		$hash = $dataItem->getSerialization();

		// Guard against a dataItem that points to itself
		$this->circularReferenceGuard->mark( $hash );

		if ( !$this->circularReferenceGuard->isCircularByRecursionFor( $hash ) ) {
			$dataItem = $this->store->getRedirectTarget( $dataItem );
		}

		$this->circularReferenceGuard->unmark( $hash );

		return $dataItem;
	}

	/**
	 * The subobject is attached to a root subject therefore using the root as
	 * identifier to allow it to be invalidated at once with all other subobjects
	 * that relate to a subject
	 */
	private function getKeyForMainSubject( DIWikiPage $subject ) {
		return md5( HashBuilder::createHashIdFromSegments(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki()
		) );
	}

}
