<?php

namespace SMW\SQLStore\Lookup;

use Onoi\Cache\Cache;
use Onoi\BlobStore\BlobStore;
use SMW\SQLStore\ValueLookupStore;
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
class CachedValueLookupStore implements ValueLookupStore {

	/**
	 * Update this version number when the serialization format
	 * changes.
	 */
	const VERSION = '1.1';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var BlobStore
	 */
	private $blobStore;

	/**
	 * @var integer
	 */
	private $valueLookupFeatures = 0;

	/**
	 * @var CircularReferenceGuard
	 */
	private $circularReferenceGuard;

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
	 * @param integer $valueLookupFeatures
	 */
	public function setValueLookupFeatures( $valueLookupFeatures ) {
		$this->valueLookupFeatures = $valueLookupFeatures;
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $valueLookupFeature
	 *
	 * @return boolean
	 */
	public function canUseValueLookupFeature( $valueLookupFeature ) {
		return $this->valueLookupFeatures === ( $this->valueLookupFeatures | $valueLookupFeature );
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

		if ( !$this->blobStore->canUse() || !$this->canUseValueLookupFeature( SMW_VL_SD ) ) {
			return $this->store->getReader()->getSemanticData( $subject, $filter );
		}

		// Use a separate container otherwise large serializations of subobjects
		// will decrease performance when combined with other lists
		$sid = $this->getKeyForMainSubject(
			$subject,
			$subject->getSubobjectName()
		);

		$container = $this->blobStore->read( $sid );

		$sdid = HashBuilder::createHashIdForContent(
			array(
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

		$this->appendToList( $sid, $subject );

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

		if ( !$this->blobStore->canUse() || !$this->canUseValueLookupFeature( SMW_VL_PL ) ) {
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
	public function getPropertyValues( DIWikiPage $subject = null, DIProperty $property, $requestOptions = null ) {

		$container = array();

		// The cache is not used for $subject === null (means all values for
		// the given property are returned)
		if ( $subject === null || !$this->blobStore->canUse() || !$this->canUseValueLookupFeature( SMW_VL_PV ) ) {
			return $this->store->getReader()->getPropertyValues( $subject, $property, $requestOptions );
		}

		// Too many subobjects in one list can kill the performance therefore split
		// the container by subobject
		$sid = $this->getKeyForMainSubject(
			$subject,
			$subject->getSubobjectName()
		);

		$container = $this->blobStore->read( $sid );

		$pvid = HashBuilder::createHashIdForContent(
			array(
				$property->getKey(),
				$property->isInverse(),
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

		$this->appendToList( $sid, $subject );

		return $result;
	}

	/**
	 * @see Store::getPropertySubjects
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage|null $subject
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions = null
	 *
	 * @return array
	 */
	public function getPropertySubjects( DIProperty $property, DataItem $dataItem = null, $requestOptions = null ) {

		// The cache is not used for $dataItem === null (means all values for
		// the given property are returned)
		if ( $dataItem === null || !$dataItem instanceof DIWikiPage || !$this->blobStore->canUse() || !$this->canUseValueLookupFeature( SMW_VL_PS ) ) {
			return $this->store->getReader()->getPropertySubjects( $property, $dataItem, $requestOptions );
		}

		// Added as linked list as we keep the container ttl different from
		// that of the main container
		$sid = $this->getKeyForMainSubject(
			$dataItem,
			'ps'
		);

		$container = $this->blobStore->read( $sid );

		$psid = HashBuilder::createHashIdForContent(
			array(
				$property->getKey(),
				$property->isInverse(),
				(array)$requestOptions,
				self::VERSION
			),
			'ps:'
		);

		if ( $container->has( $psid ) ) {
			return $container->get( $psid );
		}

		$result = $this->store->getReader()->getPropertySubjects(
			$property,
			$dataItem,
			$requestOptions
		);

		$container->set( $psid, $result );

		// We set a short lifetime (5 min) in order to cache repeated requests but
		// avoiding a complex invalidation during a subject update otherwise all
		// properties of a container would require scanning and removal
		$container->setExpiryInSeconds( 60 * 5 );

		$this->blobStore->save(
			$container
		);

		$this->appendToList( $sid, $dataItem );

		return $result;
	}

	/**
	 * Remove a cache item that appears during an alteration action (update,
	 * change, delete) to ensure that we always have the correct set of matches.
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject
	 */
	public function deleteFor( DIWikiPage $subject ) {

		if ( !$this->blobStore->canUse() ) {
			return null;
		}

		// Remove a redirect target subject directly
		$redirects = $this->getSemanticData( $subject )->getPropertyValues(
			new DIProperty( '_REDI' )
		);

		foreach ( $redirects as $redirectTarget ) {
			$this->blobStore->delete( $this->getKeyForMainSubject(
				$redirectTarget
			) );
		}

		$sid = $this->getKeyForMainSubject( $subject );

		// Remove all linked objects
		$container = $this->blobStore->read( $sid );

		if ( $container->has( 'list' ) ) {
			foreach ( array_keys( $container->get( 'list' ) ) as $id ) {
				$this->blobStore->delete( $id );
			}
		}

		$this->blobStore->delete( $sid );
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
	private function getKeyForMainSubject( DIWikiPage $subject, $suffix = '' ) {
		return md5( HashBuilder::createHashIdFromSegments(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki()
		) . $suffix );
	}

	private function appendToList( $id, $subject ) {

		// Store the id with the main subject
		$container = $this->blobStore->read(
			$this->getKeyForMainSubject( $subject )
		);

		// Use the id as key to avoid unnecessary duplicate entries when
		// employing append
		$container->append(
			'list',
			array( $id => true )
		);

		$this->blobStore->save(
			$container
		);
	}

}
