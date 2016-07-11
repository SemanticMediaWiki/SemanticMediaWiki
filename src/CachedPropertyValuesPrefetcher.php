<?php

namespace SMW;

use Onoi\BlobStore\BlobStore;
use SMWQuery as Query;

/**
 * This class should be accessed via ApplicationFactory::getCachedPropertyValuesPrefetcher
 * to ensure a singleton instance.
 *
 * The purpose of this class is to give fragmented access to frequent (hence
 * cachable) property values to ensure that the store is only used for when a
 * match can not be found and so freeing up the capacities that can equally be
 * served from a persistent cache instance.
 *
 * It is expected that as soon as the "on.before.semanticdata.update.complete"
 * event has been emitted that matchable cache entries are purged for the
 * subject in question.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CachedPropertyValuesPrefetcher {

	/**
	 * @var string
	 */
	const VERSION = '0.3';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var BlobStore
	 */
	private $blobStore;

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param BlobStore $blobStore
	 */
	public function __construct( Store $store, BlobStore $blobStore ) {
		$this->store = $store;
		$this->blobStore = $blobStore;
	}

	/**
	 * @since 2.4
	 */
	public function resetCacheFor( DIWikiPage $subject ) {
		$this->blobStore->delete( $this->getRootHashFor( $subject ) );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $subject
	 * @param DIProperty $property
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return array
	 */
	public function getPropertyValues( DIWikiPage $subject, DIProperty $property, RequestOptions $requestOptions = null ) {

		$key = $property->getKey() . ':' . $subject->getSubobjectName() . ':' . (
			$requestOptions !== null ? $requestOptions->getHash() : null
		);

		$container = $this->blobStore->read(
			$this->getRootHashFor( $subject )
		);

		if ( $container->has( $key ) ) {
			return $container->get( $key );
		}

		$dataItems = $this->store->getPropertyValues(
			$subject,
			$property,
			$requestOptions
		);

		$container->set( $key, $dataItems );

		$this->blobStore->save(
			$container
		);

		return $dataItems;
	}

	/**
	 * @since 2.4
	 *
	 * @param Query $query
	 *
	 * @return array
	 */
	public function queryPropertyValuesFor( Query $query ) {
		return $this->store->getQueryResult( $query )->getResults();
	}

	/**
	 * @since 2.4
	 *
	 * @return BlobStore
	 */
	public function getBlobStore() {
		return $this->blobStore;
	}

	/**
	 * @since 2.4
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return string
	 */
	public function getRootHashFor( DIWikiPage $subject ) {
		return md5( $subject->asBase()->getHash() . self::VERSION );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $hash
	 *
	 * @return string
	 */
	public function getHashFor( $hash ) {
		return md5( $hash . self::VERSION );
	}

}
