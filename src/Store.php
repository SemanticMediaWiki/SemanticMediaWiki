<?php

namespace SMW;

use InvalidArgumentException;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use Psr\Log\LoggerAwareTrait;
use SMW\Connection\ConnectionManager;
use SMW\Utils\Timer;
use SMW\Options;
use SMWDataItem as DataItem;
use SMWQuery;
use SMWQueryResult;
use SMWRequestOptions;
use SMWSemanticData;
use SMW\Services\Exception\ServiceNotFoundException;
use Title;

/**
 * This group contains all parts of SMW that relate to storing and retrieving
 * semantic data. SMW components that relate to semantic querying only have
 * their own group.
 *
 * @defgroup SMWStore SMWStore
 * @ingroup SMW
 */

/**
 * The abstract base class for all classes that implement access to some
 * semantic store. Besides the relevant interface, this class provides default
 * implementations for some optional methods, which inform the caller that
 * these methods are not implemented.
 *
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
 */
abstract class Store implements QueryEngine {

	use MessageReporterAwareTrait;
	use LoggerAwareTrait;

	/**
	 * Option to define whether creating updates jobs is allowed for a request
	 * or not.
	 */
	const OPT_CREATE_UPDATE_JOB = 'opt.create.update.job';

	/**
	 * @var ConnectionManager
	 */
	protected $connectionManager = null;

	/**
	 * @var Options
	 */
	protected $options = null;

///// Reading methods /////

	/**
	 * @see EntityLookup::getSemanticData
	 *
	 * @param DIWikiPage $subject
	 * @param string[]|bool $filter
	 */
	public abstract function getSemanticData( DIWikiPage $subject, $filter = false );

	/**
	 * @see EntityLookup::getPropertyValues
	 *
	 * @param $subject mixed SMWDIWikiPage or null
	 * @param $property DIProperty
	 * @param $requestoptions SMWRequestOptions
	 *
	 * @return array of DataItem
	 */
	public abstract function getPropertyValues( $subject, DIProperty $property, $requestoptions = null );

	/**
	 * @see EntityLookup::getPropertySubjects
	 *
	 * @return DIWikiPage[]
	 */
	public abstract function getPropertySubjects( DIProperty $property, $value, $requestoptions = null );

	/**
	 * Get an array of all subjects that have some value for the given
	 * property. The result is an array of DIWikiPage objects.
	 *
	 * @return DIWikiPage[]
	 */
	public abstract function getAllPropertySubjects( DIProperty $property, $requestoptions = null );

	/**
	 * @see EntityLookup::getProperties
	 *
	 * @param DIWikiPage $subject denoting the subject
	 * @param SMWRequestOptions|null $requestOptions optionally defining further options
	 *
	 * @return DataItem
	 */
	public abstract function getProperties( DIWikiPage $subject, $requestOptions = null );

	/**
	 * @see EntityLookup::getInProperties
	 *
	 * @param DataItem $object
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DataItem[]|[]
	 */
	public abstract function getInProperties( DataItem $object, $requestoptions = null );

	/**
	 * Convenience method to find the sortkey of an SMWDIWikiPage. The
	 * result is based on the contents of this store, and may differ from
	 * the MediaWiki database entry about a Title objects sortkey. If no
	 * sortkey is stored, the default sortkey (title string) is returned.
	 *
	 * @param DIWikiPage $dataItem
	 *
	 * @return string sortkey
	 */
	public function getWikiPageSortKey( DIWikiPage $dataItem ) {

		$dataItems = $this->getPropertyValues( $dataItem, new DIProperty( '_SKEY' ) );

		if ( is_array( $dataItems ) && count( $dataItems ) > 0 ) {
			return end( $dataItems )->getString();
		}

		return str_replace( '_', ' ', $dataItem->getDBkey() );
	}

	/**
	 * Convenience method to find the redirect target of a DIWikiPage
	 * or DIProperty object. Returns a dataitem of the same type that
	 * the input redirects to, or the input itself if there is no redirect.
	 *
	 * @param DataItem $dataItem
	 *
	 * @return DataItem
	 */
	public function getRedirectTarget( DataItem $dataItem ) {

		$type = $dataItem->getDIType();

		if ( $type !== DataItem::TYPE_WIKIPAGE && $type !== DataItem::TYPE_PROPERTY ) {
			throw new InvalidArgumentException( 'Store::getRedirectTarget expects a DIProperty or DIWikiPage object.' );
		}

		if ( $type === DataItem::TYPE_PROPERTY ) {

			if ( !$dataItem->isUserDefined() ) {
				return $dataItem;
			}

			$wikipage = $dataItem->getDiWikiPage();
		} elseif ( $type === DataItem::TYPE_WIKIPAGE ) {
			$wikipage = $dataItem;
		}

		$entityCache = ApplicationFactory::getInstance()->getEntityCache();
		$key = $entityCache->makeCacheKey( 'redirect', $wikipage->getHash() );

		if ( $type === DataItem::TYPE_PROPERTY && ( $serialization = $entityCache->fetch( $key ) ) !== false ) {
			return DataItem::newFromSerialization( $type, $serialization );
		}

		$dataItems = $this->getPropertyValues( $wikipage, new DIProperty( '_REDI' ) );

		if ( is_array( $dataItems ) && count( $dataItems ) > 0 ) {

			$redirectDataItem = end( $dataItems );

			if ( $type == DataItem::TYPE_PROPERTY && $redirectDataItem instanceof DIWikiPage ) {
				$dataItem = DIProperty::newFromUserLabel( $redirectDataItem->getDBkey() );
			} else {
				$dataItem = $redirectDataItem;
			}
		}

		if ( $type === DataItem::TYPE_PROPERTY ) {
			$entityCache->save( $key, $dataItem->getSerialization(), $entityCache::TTL_DAY );
			$entityCache->associate( $wikipage, $key );
		}

		return $dataItem;
	}

///// Writing methods /////

	/**
	 * Delete all semantic properties that the given subject has. This
	 * includes relations, attributes, and special properties. This does
	 * not delete the respective text from the wiki, but only clears the
	 * stored data.
	 *
	 * @param Title $subject
	 */
	public abstract function deleteSubject( Title $subject );

	/**
	 * Update the semantic data stored for some individual. The data is
	 * given as a SemanticData object, which contains all semantic data
	 * for one particular subject.
	 *
	 * @param SemanticData $data
	 */
	protected abstract function doDataUpdate( SemanticData $data );

	/**
	 * Update the semantic data stored for some individual. The data is
	 * given as a SemanticData object, which contains all semantic data
	 * for one particular subject.
	 *
	 * @param SemanticData $semanticData
	 */
	public function updateData( SemanticData $semanticData ) {

		if ( !$this->getOption( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		Timer::start( __METHOD__ );

		$applicationFactory = ApplicationFactory::getInstance();

		$subject = $semanticData->getSubject();
		$hash = $subject->getHash();

		// Deprecated since 3.1, use SMW::Store::BeforeDataUpdateComplete
		\Hooks::run( 'SMWStore::updateDataBefore', [ $this, $semanticData ] );

		\Hooks::run( 'SMW::Store::BeforeDataUpdateComplete', [ $this, $semanticData ] );

		$this->doDataUpdate( $semanticData );

		// Deprecated since 3.1, use SMW::Store::AfterDataUpdateComplete
		\Hooks::run( 'SMWStore::updateDataAfter', [ $this, $semanticData ] );

		\Hooks::run( 'SMW::Store::AfterDataUpdateComplete', [ $this, $semanticData ] );

		$rev = $semanticData->getExtensionData( 'revision_id' );
		$procTime = Timer::getElapsedTime( __METHOD__, 5 );

		$this->logger->info(
			[ 'Store', 'Update completed: {hash}', 'rev: {rev}', 'procTime: {procTime}'],
			[ 'method' => __METHOD__, 'role' => 'production', 'hash' => $hash, 'rev' => $rev, 'procTime' => $procTime ]
		);

		if ( !$this->getOption( 'smwgAutoRefreshSubject' ) || $semanticData->getOption( Enum::OPT_SUSPEND_PURGE ) ) {
			return $this->logger->info( [ 'Store', 'Skipping html, parser cache purge' ], [ 'role' => 'user' ] );
		}

		$pageUpdater = $applicationFactory->newPageUpdater();

		$pageUpdater->addPage( $subject->getTitle() );
		$pageUpdater->waitOnTransactionIdle();
		$pageUpdater->markAsPending();
		$pageUpdater->setOrigin( __METHOD__ );

		$pageUpdater->doPurgeParserCache();
		$pageUpdater->doPurgeHtmlCache();
		$pageUpdater->pushUpdate();
	}

	/**
	 * Clear all semantic data specified for some page.
	 *
	 * @param DIWikiPage $di
	 */
	public function clearData( DIWikiPage $di ) {
		$this->updateData( new SMWSemanticData( $di ) );
	}

	/**
	 * Update the store to reflect a renaming of some article. Normally
	 * this happens when moving pages in the wiki, and in this case there
	 * is also a new redirect page generated at the old position. The title
	 * objects given are only used to specify the name of the title before
	 * and after the move -- do not use their IDs for anything! The ID of
	 * the moved page is given in $pageid, and the ID of the newly created
	 * redirect, if any, is given by $redirid. If no new page was created,
	 * $redirid will be 0.
	 */
	public abstract function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 );

///// Query answering /////

	/**
	 * @note Change the signature in 3.* to avoid for subclasses to manage the
	 * hooks; keep the current signature to adhere semver for the 2.* branch
	 *
	 * Execute the provided query and return the result as an
	 * SMWQueryResult if the query was a usual instance retrieval query. In
	 * the case that the query asked for a plain string (querymode
	 * MODE_COUNT or MODE_DEBUG) a plain wiki and HTML-compatible string is
	 * returned.
	 *
	 * @param SMWQuery $query
	 *
	 * @return SMWQueryResult
	 */
	public abstract function getQueryResult( SMWQuery $query );

	/**
	 * @note Change the signature to abstract for the 3.* branch
	 *
	 * @since  2.1
	 *
	 * @param SMWQuery $query
	 *
	 * @return SMWQueryResult
	 */
	protected function fetchQueryResult( SMWQuery $query ) {
	}

///// Special page functions /////

	/**
	 * Return all properties that have been used on pages in the wiki. The
	 * result is an array of arrays, each containing a property data item
	 * and a count. The expected order is alphabetical w.r.t. to property
	 * names.
	 *
	 * If there is an error on creating some property object, then a
	 * suitable SMWDIError object might be returned in its place. Even if
	 * there are errors, the function should always return the number of
	 * results requested (otherwise callers might assume that there are no
	 * further results to ask for).
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array of array( DIProperty|SMWDIError, integer )
	 */
	public abstract function getPropertiesSpecial( $requestoptions = null );

	/**
	 * Return all properties that have been declared in the wiki but that
	 * are not used on any page. Stores might restrict here to those
	 * properties that have been given a type if they have no efficient
	 * means of accessing the set of all pages in the property namespace.
	 *
	 * If there is an error on creating some property object, then a
	 * suitable SMWDIError object might be returned in its place. Even if
	 * there are errors, the function should always return the number of
	 * results requested (otherwise callers might assume that there are no
	 * further results to ask for).
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array of DIProperty|SMWDIError
	 */
	public abstract function getUnusedPropertiesSpecial( $requestoptions = null );

	/**
	 * Return all properties that are used on some page but that do not
	 * have any page describing them. Stores that have no efficient way of
	 * accessing the set of all existing pages can extend this list to all
	 * properties that are used but do not have a type assigned to them.
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array of array( DIProperty, int )
	 */
	public abstract function getWantedPropertiesSpecial( $requestoptions = null );

	/**
	 * Return statistical information as an associative array with the
	 * following keys:
	 * - 'PROPUSES': Number of property instances (value assignments) in the datatbase
	 * - 'USEDPROPS': Number of properties that are used with at least one value
	 * - 'DECLPROPS': Number of properties that have been declared (i.e. assigned a type)
	 * - 'OWNPAGE': Number of properties with their own page
	 * - 'QUERY': Number of inline queries
	 * - 'QUERYSIZE': Represents collective query size
	 * - 'CONCEPTS': Number of declared concepts
	 * - 'SUBOBJECTS': Number of declared subobjects
	 *
	 * @return array
	 */
	public abstract function getStatistics();

	/**
	 * Store administration
	 */

	/**
	 * @private
	 *
	 * Returns store specific services. Services are registered with the store
	 * implementation and may provide different services that are only available
	 * for a particular store.
	 *
	 * @since 3.0
	 *
	 * @param string $service
	 *
	 * @return mixed
	 * @throws ServiceNotFoundException
	 */
	public function service( $service, ...$args ) {
		throw new ServiceNotFoundException( $service );
	}

	/**
	 * Setup all storage structures properly for using the store. This
	 * function performs tasks like creation of database tables. It is
	 * called upon installation as well as on upgrade: hence it must be
	 * able to upgrade existing storage structures if needed. It should
	 * return "true" if successful and return a meaningful string error
	 * message otherwise.
	 *
	 * The parameter $verbose determines whether the procedure is allowed
	 * to report on its progress. This is doen by just using print and
	 * possibly ob_flush/flush. This is also relevant for preventing
	 * timeouts during long operations. All output must be valid in an HTML
	 * context, but should preferably be plain text, possibly with some
	 * linebreaks and weak markup.
	 *
	 * @param boolean $verbose
	 *
	 * @return boolean Success indicator
	 */
	public abstract function setup( $verbose = true );

	/**
	 * Drop (delete) all storage structures created by setup(). This will
	 * delete all semantic data and possibly leave the wiki uninitialised.
	 *
	 * @param boolean $verbose
	 */
	public abstract function drop( $verbose = true );

	/**
	 * Refresh some objects in the store, addressed by numerical ids. The
	 * meaning of the ids is private to the store, and does not need to
	 * reflect the use of IDs elsewhere (e.g. page ids). The store is to
	 * refresh $count objects starting from the given $index. Typically,
	 * updates are achieved by generating update jobs. After the operation,
	 * $index is set to the next index that should be used for continuing
	 * refreshing, or to -1 for signaling that no objects of higher index
	 * require refresh. The method returns a decimal number between 0 and 1
	 * to indicate the overall progress of the refreshing (e.g. 0.7 if 70%
	 * of all objects were refreshed).
	 *
	 * The optional parameter $namespaces may contain an array of namespace
	 * constants. If given, only objects from those namespaces will be
	 * refreshed. The default value FALSE disables this feature.
	 *
	 * The optional parameter $usejobs indicates whether updates should be
	 * processed later using MediaWiki jobs, instead of doing all updates
	 * immediately. The default is TRUE.
	 *
	 * @param $index integer
	 * @param $count integer
	 * @param $namespaces mixed array or false
	 * @param $usejobs boolean
	 *
	 * @return float between 0 and 1 to indicate the overall progress of the refreshing
	 */
	public abstract function refreshData( &$index, $count, $namespaces = false, $usejobs = true );

	/**
	 * Setup the store.
	 *
	 * @since 1.8
	 *
	 * @param bool $verbose
	 * @param Options|null $options
	 *
	 * @return boolean Success indicator
	 */
	public static function setupStore( $verbose = true, $options = null ) {

		$store = StoreFactory::getStore();
		$messageReporter = null;

		// See notes in ExtensionSchemaUpdates
		if ( is_bool( $verbose ) ) {
			$verbose = $verbose;
		}

		if ( isset( $options['verbose'] ) ) {
			$verbose = $options['verbose'];
		}

		if ( isset( $options['options'] ) ) {
			$options = $options['options'];
		}

		if ( $options instanceof Options && $options->has( 'messageReporter' ) ) {
			$messageReporter = $options->get( 'messageReporter' );
		}

		if ( $messageReporter !== null ) {
			$store->setMessageReporter( $messageReporter );

			$messageReporter->reportMessage(
				"\nSemantic MediaWiki " . SMW_VERSION . " updater\n"
			);
		}

		return $store->setup( $options );
	}

	/**
	 * @since 2.5
	 *
	 * @return Options
	 */
	public function getOptions() {

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		return $this->options;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		return $this->options->set( $key, $value );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		return $this->options->safeGet( $key, $default );
	}

	/**
	 * @since 2.0
	 */
	public function clear() {

		if ( $this->connectionManager !== null ) {
			$this->connectionManager->releaseConnections();
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string|null $type
	 *
	 * @return array
	 */
	public function getInfo( $type = null ) {
		return [];
	}

	/**
	 * @since 2.1
	 *
	 * @param ConnectionManager $connectionManager
	 */
	public function setConnectionManager( ConnectionManager $connectionManager ) {
		$this->connectionManager = $connectionManager;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $type
	 *
	 * @return mixed
	 */
	public function getConnection( $type ) {

		if ( $this->connectionManager === null ) {
			$this->connectionManager = ApplicationFactory::getInstance()->getConnectionManager();
		}

		return $this->connectionManager->getConnection( $type );
	}

}
