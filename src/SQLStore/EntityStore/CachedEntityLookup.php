<?php

namespace SMW\SQLStore\EntityStore;

use Onoi\BlobStore\BlobStore;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\HashBuilder;
use SMW\EntityLookup;
use SMW\Store;
use SMW\Localizer;
use SMWDataItem as DataItem;
use SMW\SemanticData;
use SMW\RequestOptions;

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
class CachedEntityLookup implements EntityLookup {

	/**
	 * Update this version number when the serialization format
	 * changes.
	 */
	const VERSION = '1.1';

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var RedirectTargetLookup
	 */
	private $redirectTargetLookup;

	/**
	 * @var BlobStore
	 */
	private $blobStore;

	/**
	 * @var integer
	 */
	private $valueLookupFeatures = 0;

	/**
	 * @since 2.3
	 *
	 * @param EntityLookup $entityLookup
	 * @param RedirectTargetLookup $redirectTargetLookup
	 * @param BlobStore $blobStore
	 */
	public function __construct( EntityLookup $entityLookup, RedirectTargetLookup $redirectTargetLookup, BlobStore $blobStore ) {
		$this->entityLookup = $entityLookup;
		$this->redirectTargetLookup = $redirectTargetLookup;
		$this->blobStore = $blobStore;
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $valueLookupFeatures
	 */
	public function setCachedLookupFeatures( $valueLookupFeatures ) {
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
	 * @see EntityLookup::getSemanticData
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false ) {

		if ( !$this->blobStore->canUse() || !$this->canUseValueLookupFeature( SMW_VL_SD ) ) {
			return $this->entityLookup->getSemanticData( $subject, $filter );
		}

		// Use a separate container otherwise large serializations of subobjects
		// will decrease performance when combined with other lists
		$sid = $this->getHashFrom(
			$subject,
			$subject->getSubobjectName()
		);

		$container = $this->blobStore->read( $sid );

		// Make sure that when switching user languages, user labels etc.
		// are appropriately generated
		$userLang = Localizer::getInstance()->getUserLanguage()->getCode();

		$sdid = HashBuilder::createFromContent(
			array(
				(array)$filter,
				self::VERSION
			),
			'sd:'. $userLang . ':'
		);

		if ( $container->has( $sdid ) ) {
			return $container->get( $sdid );
		}

		$semanticData = $this->entityLookup->getSemanticData(
			$subject,
			$filter
		);

		$semanticData->setOption(
			SemanticData::OPT_LAST_MODIFIED,
			wfTimestamp( TS_UNIX )
		);

		$container->set( $sdid, $semanticData );

		$this->blobStore->save(
			$container
		);

		$this->appendToList( $sid, $subject );

		return $semanticData;
	}

	/**
	 * @see EntityLookup::getProperties
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getProperties( DIWikiPage $subject, RequestOptions $requestOptions = null ) {

		if ( !$this->blobStore->canUse() || !$this->canUseValueLookupFeature( SMW_VL_PL ) ) {
			return $this->entityLookup->getProperties( $subject, $requestOptions );
		}

		$container = $this->blobStore->read(
			$this->getHashFrom( $subject )
		);

		$plid = HashBuilder::createFromContent(
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

		$result = $this->entityLookup->getProperties(
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
	 * @see EntityLookup::getPropertyValues
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getPropertyValues( DIWikiPage $subject = null, DIProperty $property, RequestOptions $requestOptions = null ) {

		// The cache is not used for $subject === null (means all values for
		// the given property are returned)
		if ( $subject === null || !$this->blobStore->canUse() || !$this->canUseValueLookupFeature( SMW_VL_PV ) ) {
			return $this->entityLookup->getPropertyValues( $subject, $property, $requestOptions );
		}

		// Too many subobjects in one list can kill the performance therefore split
		// the container by subobject
		$sid = $this->getHashFrom(
			$subject,
			$subject->getSubobjectName()
		);

		$container = $this->blobStore->read( $sid );

		$pvid = HashBuilder::createFromContent(
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

		$result = $this->entityLookup->getPropertyValues(
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
	 * @see EntityLookup::getPropertySubjects
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getPropertySubjects( DIProperty $property, DataItem $dataItem = null, RequestOptions $requestOptions = null ) {

		// The cache is not used for $dataItem === null (means all values for
		// the given property are returned)
		if ( $dataItem === null || !$dataItem instanceof DIWikiPage || !$this->blobStore->canUse() || !$this->canUseValueLookupFeature( SMW_VL_PS ) ) {
			return $this->entityLookup->getPropertySubjects( $property, $dataItem, $requestOptions );
		}

		// Added as linked list as we keep the container ttl different from
		// that of the main container
		$sid = $this->getHashFrom(
			$dataItem,
			'ps'
		);

		$container = $this->blobStore->read( $sid );

		$psid = HashBuilder::createFromContent(
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

		$result = $this->entityLookup->getPropertySubjects(
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
	 * @see EntityLookup::getAllPropertySubjects
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getAllPropertySubjects( DIProperty $property, RequestOptions $requestOptions = null  ) {
		return $this->entityLookup->getAllPropertySubjects( $property, $requestOptions );
	}

	/**
	 * @see EntityLookup::getInProperties
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getInProperties( DataItem $object, RequestOptions $requestOptions = null ) {
		return $this->entityLookup->getInProperties( $object, $requestOptions );
	}

	/**
	 * Remove a cache item that appears during an alteration action (update,
	 * change, delete) to ensure that we always have the correct set of matches.
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage|null $subject
	 */
	public function resetCacheBy( DIWikiPage $subject = null ) {

		if ( !$this->blobStore->canUse() || $subject === null ) {
			return null;
		}

		// Remove a redirect target subject directly
		$redirects = $this->getSemanticData( $subject )->getPropertyValues(
			new DIProperty( '_REDI' )
		);

		foreach ( $redirects as $redirectTarget ) {
			$this->blobStore->delete( $this->getHashFrom(
				$redirectTarget
			) );
		}

		$sid = $this->getHashFrom( $subject );

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
			$dataItems[] = $this->redirectTargetLookup->findRedirectTarget( $dataItem );
		}

		return $dataItems;
	}

	/**
	 * The subobject is attached to a root subject therefore using the root as
	 * identifier to allow it to be invalidated at once with all other subobjects
	 * that relate to a subject
	 */
	private function getHashFrom( DIWikiPage $subject, $suffix = '' ) {
		return md5( HashBuilder::createHashIdFromSegments(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki()
		) . $suffix );
	}

	private function appendToList( $id, $subject ) {

		// Store the id with the main subject
		$container = $this->blobStore->read(
			$this->getHashFrom( $subject )
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
