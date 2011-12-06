<?php
/**
 * Basic abstract classes for SMW's storage abstraction layer.
 *
 * @file
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
 */

/**
 * This group contains all parts of SMW that relate to storing and retrieving
 * semantic data. SMW components that relate to semantic querying only have
 * their own group.
 *
 * @defgroup SMWStore SMWStore
 * @ingroup SMW
 */

/**
 * Small data container class for describing filtering conditions on the string
 * label of some entity. States that a given string should either be prefix,
 * postfix, or some arbitrary part of labels.
 *
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
 */
class SMWStringCondition {
	const STRCOND_PRE = 0;
	const STRCOND_POST = 1;
	const STRCOND_MID = 2;

	/**
	 * String to match.
	 */
	public $string;

	/**
	 * Condition. One of STRCOND_PRE (string matches prefix),
	 * STRCOND_POST (string matches postfix), STRCOND_MID
	 * (string matches to some inner part).
	 */
	public $condition;

	public function __construct( $string, $condition ) {
		$this->string = $string;
		$this->condition = $condition;
	}
}

/**
 * Container object for various options that can be used when retrieving
 * data from the store. These options are mostly relevant for simple,
 * direct requests -- inline queries may require more complex options due
 * to their more complex structure.
 * Options that should not be used or where default values should be used
 * can be left as initialised.
 *
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
 */
class SMWRequestOptions {

	/**
	 * The maximum number of results that should be returned.
	 */
	public $limit = -1;

	/**
	 * A numerical offset. The first $offset results are skipped.
	 * Note that this does not imply a defined order of results
	 * (see SMWRequestOptions->$sort below).
	 */
	public $offset = 0;

	/**
	 * Should the result be ordered? The employed order is defined
	 * by the type of result that are requested: wiki pages and strings
	 * are ordered alphabetically, whereas other data is ordered
	 * numerically. Usually, the order should be fairly "natural".
	 */
	public $sort = false;

	/**
	 * If SMWRequestOptions->$sort is true, this parameter defines whether
	 * the results are ordered in ascending or descending order.
	 */
	public $ascending = true;

	/**
	 * Specifies a lower or upper bound for the values returned by the query.
	 * Whether it is lower or upper is specified by the parameter "ascending"
	 * (true->lower, false->upper).
	 */
	public $boundary = null;

	/**
	 * Specifies whether or not the requested boundary should be returned
	 * as a result.
	 */
	public $include_boundary = true;

	/**
	 * An array of string conditions that are applied if the result has a
	 * string label that can be subject to those patterns.
	 */
	private $stringcond = array();

	/**
	 * Set a new string condition applied to labels of results (if available).
	 *
	 * @param $string string to match
	 * @param $condition integer type of condition, one of STRCOND_PRE, STRCOND_POST, STRCOND_MID
	 */
	public function addStringCondition( $string, $condition ) {
		$this->stringcond[] = new SMWStringCondition( $string, $condition );
	}

	/**
	 * Return the specified array of SMWStringCondition objects.
	 */
	public function getStringConditions() {
		return $this->stringcond;
	}

}


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
abstract class SMWStore {

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
	 */
	public abstract function getSemanticData( SMWDIWikiPage $subject, $filter = false );

	/**
	 * Get an array of all property values stored for the given subject and
	 * property. The result is an array of SMWDataItem objects.
	 *
	 * If called with $subject == null, all values for the given property
	 * are returned.
	 *
	 * @param $subject mixed SMWDIWikiPage or null
	 * @param $property SMWDIProperty
	 * @param $requestoptions SMWRequestOptions
	 *
	 * @return array of SMWDataItem
	 */
	public abstract function getPropertyValues( $subject, SMWDIProperty $property, $requestoptions = null );

	/**
	 * Get an array of all subjects that have the given value for the given
	 * property. The result is an array of SMWDIWikiPage objects. If null
	 * is given as a value, all subjects having that property are returned.
	 */
	public abstract function getPropertySubjects( SMWDIProperty $property, $value, $requestoptions = null );

	/**
	 * Get an array of all subjects that have some value for the given
	 * property. The result is an array of SMWDIWikiPage objects.
	 */
	public abstract function getAllPropertySubjects( SMWDIProperty $property, $requestoptions = null );

	/**
	 * Get an array of all properties for which the given subject has some
	 * value. The result is an array of SMWDIProperty objects.
	 *
	 * @param $subject SMWDIWikiPage denoting the subject
	 * @param $requestoptions SMWRequestOptions optionally defining further options
	 */
	public abstract function getProperties( SMWDIWikiPage $subject, $requestoptions = null );

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
	 * @param $wikiPage SMWDIWikiPage to find the sortkey for
	 * @return string sortkey
	 */
	public function getWikiPageSortKey( SMWDIWikiPage $wikiPage ) {
		$sortkeyDataItems = $this->getPropertyValues( $wikiPage, new SMWDIProperty( '_SKEY' ) );

		if ( count( $sortkeyDataItems ) > 0 ) {
			return end( $sortkeyDataItems )->getString();
		} else {
			return str_replace( '_', ' ', $wikiPage->getDBkey() );
		}
	}

	/**
	 * Convenience method to find the redirect target of an SMWDIWikiPage
	 * or SMWDIProperty object. Returns a dataitem of the same type that
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
			throw new InvalidArgumentException( 'SMWStore::getRedirectTarget() expects an object of type SMWDIProperty or SMWDIWikiPage.' );
		}

		$redirectDataItems = $this->getPropertyValues( $wikipage, new SMWDIProperty( '_REDI' ) );
		if ( count( $redirectDataItems ) > 0 ) {
			if ( $dataItem->getDIType() == SMWDataItem::TYPE_PROPERTY ) {
				return new SMWDIProperty( end( $redirectDataItems )->getDBkey() );
			} else {
				return end( $redirectDataItems );
			}
		} else {
			return $dataItem;
		}
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
	 * given as a SMWSemanticData object, which contains all semantic data
	 * for one particular subject.
	 *
	 * @param SMWSemanticData $data
	 */
	public abstract function doDataUpdate( SMWSemanticData $data );

	/**
	 * Update the semantic data stored for some individual. The data is
	 * given as a SMWSemanticData object, which contains all semantic data
	 * for one particular subject.
	 *
	 * @param $data SMWSemanticData
	 */
	public function updateData( SMWSemanticData $data ) {
		wfRunHooks( 'SMWStore::updateDataBefore', array( $this, $data ) );

		// Invalidate the page, so data stored on it gets displayed immediately in queries.
		global $smwgAutoRefreshSubject;
		if ( $smwgAutoRefreshSubject && !wfReadOnly() ) {
			$title = Title::makeTitle( $data->getSubject()->getNamespace(), $data->getSubject()->getDBkey() );
			$dbw = wfGetDB( DB_MASTER );

			$dbw->update(
				'page',
				array( 'page_touched' => $dbw->timestamp( time() + 4 ) ),
				$title->pageCond(),
				__METHOD__
			);

			HTMLFileCache::clearFileCache( $title );
	    }

		$this->doDataUpdate( $data );

		wfRunHooks( 'SMWStore::updateDataAfter', array( $this, $data ) );
	}

	/**
	 * Clear all semantic data specified for some page.
	 *
	 * @param SMWDIWikiPage $di
	 */
	public function clearData( SMWDIWikiPage $di ) {
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

///// Special page functions /////

	/**
	 * Return all properties that have been used on pages in the wiki. The
	 * result is an array of arrays, each containing a property title and a
	 * count. The expected order is alphabetical w.r.t. to property title
	 * texts.
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array
	 */
	public abstract function getPropertiesSpecial( $requestoptions = null );

	/**
	 * Return all properties that have been declared in the wiki but that
	 * are not used on any page. Stores might restrict here to those
	 * properties that have been given a type if they have no efficient
	 * means of accessing the set of all pages in the property namespace.
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array of SMWDIProperty
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
	 * @return array of array( SMWDIProperty, int )
	 */
	public abstract function getWantedPropertiesSpecial( $requestoptions = null );

	/**
	 * Return statistical information as an associative array with the
	 * following keys:
	 * - 'PROPUSES': Number of property instances (value assignments) in the datatbase
	 * - 'USEDPROPS': Number of properties that are used with at least one value
	 * - 'DECLPROPS': Number of properties that have been declared (i.e. assigned a type)
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
	 * @return decimal between 0 and 1 to indicate the overall progress of the refreshing
	 */
	public abstract function refreshData( &$index, $count, $namespaces = false, $usejobs = true );

	/**
	 * Generate textual debug output that shows an arbitrary list of
	 * informative fields. Used for formatting query debug output.
	 *
	 * @note All strings given must be usable and safe in wiki and HTML
	 * contexts.
	 *
	 * @param $storeName string name of the storage backend for which this is generated
	 * @param $entries array of name => value of informative entries to display
	 * @param $query SMWQuery or null, if given add basic data about this query as well
	 * @return string
	 */
	public static function formatDebugOutput( $storeName, array $entries, $query = null ) {
		if ( !is_null( $query ) ) {
			$preEntries = array();
			$preEntries['Generated Wiki-Query'] = '<pre>' . str_replace( '[', '&#x005B;', $query->getDescription()->getQueryString() ) . '</pre>';
			$preEntries['Query Metrics'] = 'Query-Size:' . $query->getDescription()->getSize() . '<br />' .
						'Query-Depth:' . $query->getDescription()->getDepth();
			$entries = array_merge( $preEntries, $entries );

			$errors = '';
			foreach ( $query->getErrors() as $error ) {
				$errors .= $error . '<br />';
			}
			if ( $errors === '' ) {
				$errors = 'None';
			}
			$entries['Errors and Warnings'] = $errors;
		}

		$result = '<div style="border: 5px dotted #A1FB00; background: #FFF0BD; padding: 20px; ">' .
		          "<h3>Debug Output by $storeName</h3>";
		foreach ( $entries as $header => $information ) {
			$result .= "<h4>$header</h4>";
			if ( $information !== '' ) {
				$result .= "$information";
			}
		}
		$result .= '</div>';
		return $result;
	}

}