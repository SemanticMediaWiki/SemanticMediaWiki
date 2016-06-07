<?php

namespace SMW;

use SMWDataItem;
use SMWQuery;
use SMWQueryResult;
use SMWRequestOptions;
use SMWSemanticData;
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
abstract class Store {

	/**
	 * @var boolean
	 */
	private $updateJobsEnabledState = true;

	/**
	 * @var ConnectionManager
	 */
	protected $connectionManager = null;

///// Reading methods /////

	/**
	 * Retrieve all data stored about the given subject and return it as a
	 * SMWSemanticData container. There are no options: it just returns all
	 * available data as shown in the page's Factbox.
	 * $filter is an array of strings that are datatype IDs. If given, the
	 * function will avoid any work that is not necessary if only
	 * properties of these types are of interest.
	 *
	 * @note There is no guarantee that the store does not retrieve more
	 * data than requested when a filter is used. Filtering just ensures
	 * that only necessary requests are made, i.e. it improves performance.
	 *
	 * @param DIWikiPage $subject
	 * @param string[]|bool $filter
	 */
	public abstract function getSemanticData( DIWikiPage $subject, $filter = false );

	/**
	 * Get an array of all property values stored for the given subject and
	 * property. The result is an array of DataItem objects.
	 *
	 * If called with $subject == null, all values for the given property
	 * are returned.
	 *
	 * @param $subject mixed SMWDIWikiPage or null
	 * @param $property DIProperty
	 * @param $requestoptions SMWRequestOptions
	 *
	 * @return array of SMWDataItem
	 */
	public abstract function getPropertyValues( $subject, DIProperty $property, $requestoptions = null );

	/**
	 * Get an array of all subjects that have the given value for the given
	 * property. The result is an array of DIWikiPage objects. If null
	 * is given as a value, all subjects having that property are returned.
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
	 * Get an array of all properties for which the given subject has some
	 * value. The result is an array of DIProperty objects.
	 *
	 * @param DIWikiPage $subject denoting the subject
	 * @param SMWRequestOptions|null $requestOptions optionally defining further options
	 *
	 * @return SMWDataItem
	 */
	public abstract function getProperties( DIWikiPage $subject, $requestOptions = null );

	/**
	 * Get an array of all properties for which there is some subject that
	 * relates to the given value. The result is an array of SMWDIWikiPage
	 * objects.
	 * @note In some stores, this function might be implemented partially
	 * so that only values of type Page (_wpg) are supported.
	 */
	public abstract function getInProperties( SMWDataItem $object, $requestoptions = null );

	/**
	 * Convenience method to find the sortkey of an SMWDIWikiPage. The
	 * result is based on the contents of this store, and may differ from
	 * the MediaWiki database entry about a Title objects sortkey. If no
	 * sortkey is stored, the default sortkey (title string) is returned.
	 *
	 * @param $wikiPage DIWikiPage to find the sortkey for
	 * @return string sortkey
	 */
	public function getWikiPageSortKey( DIWikiPage $wikiPage ) {
		$sortkeyDataItems = $this->getPropertyValues( $wikiPage, new DIProperty( '_SKEY' ) );

		if ( count( $sortkeyDataItems ) > 0 ) {
			return end( $sortkeyDataItems )->getString();
		} else {
			return str_replace( '_', ' ', $wikiPage->getDBkey() );
		}
	}

	/**
	 * Convenience method to find last modified MW timestamp for a subject that
	 * has been added using the storage-engine.
	 *
	 * @since 2.3
	 *
	 * @param DIWikiPage $wikiPage
	 *
	 * @return integer
	 */
	public function getWikiPageLastModifiedTimestamp( DIWikiPage $wikiPage ) {

		$dataItems = $this->getPropertyValues( $wikiPage, new DIProperty( '_MDAT' ) );

		if ( $dataItems !== array() ) {
			return end( $dataItems )->getMwTimestamp( TS_MW );
		}

		return 0;
	}

	/**
	 * Convenience method to find the redirect target of a DIWikiPage
	 * or DIProperty object. Returns a dataitem of the same type that
	 * the input redirects to, or the input itself if there is no redirect.
	 *
	 * @param $dataItem SMWDataItem to find the redirect for.
	 * @return SMWDataItem
	 */
	public function getRedirectTarget( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_PROPERTY ) {
			if ( !$dataItem->isUserDefined() ) {
				return $dataItem;
			}
			$wikipage = $dataItem->getDiWikiPage();
		} elseif ( $dataItem->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
			$wikipage = $dataItem;
		} else {
			throw new InvalidArgumentException( 'SMWStore::getRedirectTarget() expects an object of type IProperty or SMWDIWikiPage.' );
		}

		$hash = $wikipage->getHash();
		$poolCache = InMemoryPoolCache::getInstance()->getPoolCacheFor( 'store.redirectTarget.lookup' );

		if ( $poolCache->contains( $hash ) ) {
			return $poolCache->fetch( $hash );
		}

		$redirectDataItems = $this->getPropertyValues( $wikipage, new DIProperty( '_REDI' ) );

		if ( count( $redirectDataItems ) > 0 ) {

			$redirectDataItem = end( $redirectDataItems );

			if ( $dataItem->getDIType() == SMWDataItem::TYPE_PROPERTY && $redirectDataItem instanceof DIWikiPage ) {
				$dataItem = DIProperty::newFromUserLabel( $redirectDataItem->getDBkey() );
			} else {
				$dataItem = $redirectDataItem;
			}

			$poolCache->save( $hash, $dataItem );
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

		if ( !ApplicationFactory::getInstance()->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		$subject = $semanticData->getSubject();

		$dispatchContext = EventHandler::getInstance()->newDispatchContext();
		$dispatchContext->set( 'subject', $subject );

		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'on.before.semanticdata.update.complete',
			$dispatchContext
		);

		/**
		 * @since 1.6
		 */
		\Hooks::run( 'SMWStore::updateDataBefore', array( $this, $semanticData ) );

		$this->doDataUpdate( $semanticData );

		/**
		 * @since 1.6
		 */
		\Hooks::run( 'SMWStore::updateDataAfter', array( $this, $semanticData ) );

		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'on.after.semanticdata.update.complete',
			$dispatchContext
		);
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

///// Setup store /////

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
	 * context, but should preferrably be plain text, possibly with some
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
	 *
	 * @return boolean Success indicator
	 */
	public static function setupStore( $verbose = true ) {
		$result = StoreFactory::getStore()->setup( $verbose );
		\Hooks::run( 'smwInitializeTables' );
		return $result;
	}

	/**
	 * Returns the tables that should be added via the
	 * https://www.mediawiki.org/wiki/Manual:Hooks/ParserTestTables
	 * hook when it's run.
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function getParserTestTables() {
		return array();
	}

	/**
	 * @since 2.0
	 */
	public function clear() {
		$this->connectionManager->releaseConnections();
		InMemoryPoolCache::getInstance()->resetPoolCacheFor( 'store.redirectTarget.lookup' );
	}

	/**
	 * @since 2.1
	 *
	 * @param boolean $status
	 */
	public function setUpdateJobsEnabledState( $status ) {
		$this->updateJobsEnabledState = $status;
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function getUpdateJobsEnabledState() {
		return $this->updateJobsEnabledState && $GLOBALS['smwgEnableUpdateJobs'];
	}

	/**
	 * @since 2.1
	 *
	 * @param ConnectionManager $connectionManager
	 *
	 * @return Store
	 */
	public function setConnectionManager( ConnectionManager $connectionManager ) {
		$this->connectionManager = $connectionManager;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $connectionTypeId
	 *
	 * @return mixed
	 */
	public function getConnection( $connectionTypeId ) {

		if ( $this->connectionManager === null ) {
			$this->setConnectionManager( new ConnectionManager() );
		}

		return $this->connectionManager->getConnection( $connectionTypeId );
	}

}
