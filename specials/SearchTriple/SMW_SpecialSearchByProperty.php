<?php
/**
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * A special page to search for entities that have a certain property with
 * a certain value.
 *
 * @author Denny Vrandecic
 * @author Daniel Herzig
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $wgAjaxExportList;
$wgAjaxExportList[] = "smwfGetValues";

// function smwfGetValues($p, $v) { return SMWSearchByProperty::getSuggestedValues($p, $v); }

/**
 * This special page for Semantic MediaWiki implements a
 * view on a relation-object pair,i.e. a typed backlink.
 * For example, it shows me all persons born in Croatia,
 * or all winners of the Academy Award for best actress.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWSearchByProperty extends SpecialPage {

	/// string  Name of the property searched for
	private $propertystring = "";
	/// SMWPropertyValue  The property that is searched for
	private $property = null;
	/// string  Name of the value that is searched for
	private $valuestring = "";
	/// SMWDataValue  The value that is searched for
	private $value = null;
	/// How many results should be displayed
	private $limit = 20;
	/// At what position are we currently
	private $offset = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'SearchByProperty' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	/**
	 * Main entry point for Special Pages. Gets all required parameters.
	 *
	 * @param[in] $query string  Given by MediaWiki
	 */
	public function execute( $query ) {
		global $wgRequest, $wgOut;
		$this->setHeaders();

		// get the GET parameters
		$this->propertystring = $wgRequest->getVal( 'property' );
		$this->valuestring = $wgRequest->getVal( 'value' );
		$params = SMWInfolink::decodeParameters( $query, false );
		reset( $params );
		// no GET parameters? Then try the URL
		if ( $this->propertystring == '' ) $this->propertystring = current( $params );
		if ( $this->valuestring == '' ) $this->valuestring = next( $params );

		$this->valuestring = str_replace( "&nbsp;", " ", $this->valuestring );

		$this->property = SMWPropertyValue::makeUserProperty( $this->propertystring );
		if ( !$this->property->isValid() ) {
			$this->propertystring = '';
		} else {
			$this->propertystring = $this->property->getWikiValue();
			$this->value = SMWDataValueFactory::newPropertyObjectValue( $this->property, $this->valuestring );
			if ( $this->value->isValid() ) {
				$this->valuestring = $this->value->getWikiValue();
			} else {
				$this->value = null;
			}
		}

		$limitstring = $wgRequest->getVal( 'limit' );
		if ( is_numeric( $limitstring ) ) $this->limit =  intval( $limitstring );
		$offsetstring = $wgRequest->getVal( 'offset' );
		if ( is_numeric( $offsetstring ) ) $this->offset = intval( $offsetstring );

		$wgOut->addHTML( $this->displaySearchByProperty() );
		$wgOut->addHTML( $this->queryForm() );
		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
	}

	/**
	 * Returns the HTML for the complete search by property.
	 *
	 * @return string  HTML of the search by property function
	 */
	private function displaySearchByProperty() {
		global $wgUser, $wgOut, $smwgSearchByPropertyFuzzy;
		$skin = $wgUser->getSkin();

		if ( $this->propertystring == '' ) {
			return '<p>' . wfMsg( 'smw_sbv_docu' ) . "</p>\n";
		}
		
		if ( ( $this->value == null ) || !$this->value->isValid() ) {
			return '<p>' . wfMsg( 'smw_sbv_novalue', $this->property->getShortHTMLText( $skin ) ) . "</p>\n";
		}

		$wgOut->setPagetitle( $this->property->getWikiValue() . ' ' . $this->value->getShortHTMLText( null ) );
		$html = '';

		$exact = $this->getExactResults();
		$count = count( $exact );
		
		if ( ( $count < ( $this->limit / 3 ) ) && ( $this->value->isNumeric() ) && $smwgSearchByPropertyFuzzy ) {
			$greater = $this->getNearbyResults( $count, true );
			$lesser = $this->getNearbyResults( $count, false );
			// Calculate how many greater and lesser results should be displayed
			$cG = count( $greater );
			$cL = count( $lesser );
			if ( ( $cG + $cL + $count ) > $this->limit ) {
				$l = $this->limit - $count;
				$lhalf = round( $l / 2 );
				if ( $lhalf < $cG ) {
					if ( $lhalf < $cL ) {
						$cL = $lhalf; $cG = $lhalf;
					} else {
						$cG = $this->limit - ( $count + $cL );
					}
				} else {
					$cL = $this->limit - ( $count + $cG );
				}
			}
			if ( ( $cG + $cL + $count ) == 0 )
				$html .= wfMsg( 'smw_result_noresults' );
			else {
				$html .= wfMsg( 'smw_sbv_displayresultfuzzy', $this->property->getShortHTMLText( $skin ), $this->value->getShortHTMLText( $skin ) ) . "<br />\n";
				$html .= $this->displayResults( $lesser, $cL, false );
				if ( $count == 0 ) {
					$html .= " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><strong><small>(" . $this->value->getLongHTMLText() . ")</small></strong></em>\n";
				} else {
					$html .= $this->displayResults( $exact, $count, true, true );
				}
				$html .= $this->displayResults( $greater, $cG );
			}
		} else {
			$html .= wfMsg( 'smw_sbv_displayresult', $this->property->getShortHTMLText( $skin ), $this->value->getShortHTMLText( $skin ) ) . "<br />\n";
			if ( 0 == $count ) {
				$html .= wfMsg( 'smw_result_noresults' );
			} else {
				$navi = $this->getNavigationBar( $count );
				if ( ( $this->offset > 0 ) || ( $count > $this->limit ) ) $html .= $navi;
				$html .= $this->displayResults( $exact, $this->limit );
				if ( ( $this->offset > 0 ) || ( $count > $this->limit ) ) $html .= $navi;
			}
		}

		$html .= '<p>&nbsp;</p>';
		return $html;
	}

	/**
	 * Creates the HTML for a bullet list with all the results of the set query.
	 *
	 * @param[in] $results array of array of SMWWikiPageValue, SMWDataValue  The entity and its datavalue
	 * @param[in] $number int  How many results should be displayed? -1 for all
	 * @param[in] $first bool  If less results should be displayed than given, should they show the first $number results, or the last $number results?
	 * @param[in] $highlight bool  Should the results be highlighted?
	 * @return string  HTML with the bullet list and a header
	 */
	private function displayResults( $results, $number = - 1, $first = true, $highlight = false ) {
		global $wgUser;
		$skin = $wgUser->getSkin();

		$html  = "<ul>\n";

		if ( !$first && ( $number > 0 ) ) while ( count( $results ) > $number ) array_shift( $results );
		while ( $results && $number != 0 ) {
			$result = array_shift( $results );
			$thing = $result[0]->getLongHTMLText( $skin );
			$browselink = ( $result[0]->getTypeId() == '_wpg' ) ?
			              '&nbsp;&nbsp;' . SMWInfolink::newBrowsingLink( '+', $result[0]->getShortHTMLText() )->getHTML( $skin ):'';
			$html .= '<li>' . $thing . $browselink;
			if ( ( $this->value != $result[1] ) || $highlight ) $html .= " <em><small>(" . $result[1]->getLongHTMLText( $skin ) . ")</small></em>";
			$html .= "</li>";
			if ( $highlight ) $html = "<strong>" . $html . "</strong>";
			$html .= "\n";
			$number--;
		}
		$html .= "</ul>\n";

		return $html;
	}

	/**
	 * Creates the HTML for a Navigation bar for too many results.
	 * Most of the parameters are taken from the object members.
	 *
	 * @param[in] $count int  How many results are currently displayed?
	 * @return string  HTML with the navigation bar
	 */
	private function getNavigationBar( $count ) {
		global $wgUser, $smwgQMaxInlineLimit, $smwgMW_1_14;
		$skin = $wgUser->getSkin();

		if ( $this->offset > 0 )
			$navigation = '<a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'SearchByProperty', 'offset=' . max( 0, $this->offset - $this->limit ) . '&limit=' . $this->limit . '&property=' . urlencode( $this->property->getWikiValue() ) . '&value=' . urlencode( $this->value->getWikiValue() ) ) ) . '">' . wfMsg( 'smw_result_prev' ) . '</a>';
		else
			$navigation = wfMsg( 'smw_result_prev' );

		$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg( 'smw_result_results' ) . ' ' . ( $this->offset + 1 ) . '&ndash; ' . ( $this->offset + min( $count, $this->limit ) ) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

		if ( $count > $this->limit ) {
			$navigation .= ' <a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'SearchByProperty', 'offset=' . ( $this->offset + $this->limit ) . '&limit=' . $this->limit . '&property=' . urlencode( $this->property->getWikiValue() ) . '&value=' . urlencode( $this->value->getWikiValue() ) ) )  . '">' . wfMsg( 'smw_result_next' ) . '</a>';
		} else {
			$navigation .= wfMsg( 'smw_result_next' );
		}

		$max = false; $first = true;
		foreach ( array( 20, 50, 100, 250, 500 ) as $l ) {
			if ( $max ) continue;
			if ( $first ) {
				$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(';
				$first = false;
			} else {
				$navigation .= ' ' . ( $smwgMW_1_14 ? wfMsgExt( 'pipe-separator' , 'escapenoentities' ):'|' ) . ' ';
			}
			if ( $l > $smwgQMaxInlineLimit ) {
				$l = $smwgQMaxInlineLimit;
				$max = true;
			}
			if ( $this->limit != $l ) {
				$navigation .= '<a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'SearchByProperty', 'offset=' . $this->offset . '&limit=' . $l . '&property=' . urlencode( $this->property->getWikiValue() ) . '&value=' . urlencode( $this->value->getWikiValue() ) ) ) . '">' . $l . '</a>';
			} else {
				$navigation .= '<b>' . $l . '</b>';
			}
		}
		$navigation .= ')';
		return $navigation;
	}

	/**
	 * Returns all results that have exactly the value on the property.
	 *
	 * @return array of array of SMWWikiPageValue, SMWDataValue with the first being the entity, and the second the value
	 */
	private function getExactResults() {
		$options = new SMWRequestOptions();
		$options->limit = $this->limit + 1;
		$options->offset = $this->offset;
		$options->sort = true;

		$res = &smwfGetStore()->getPropertySubjects( $this->property, $this->value, $options );
		$results = array();
		foreach ( $res as $result )
			array_push( $results, array( $result, $this->value ) );

		return $results;
	}

	/**
	 * Returns all results that have a value near to the searched for value
	 * on the property, ordered, and sorted by ending with the smallest one.
	 *
	 * @param[in] $count int  How many entities have the exact same value on the property?
	 * @param[in] $greater bool  Should the values be bigger? Set false for smaller values
	 * @return array of array of SMWWikiPageValue, SMWDataValue with the first being the entity, and the second the value
	 */
	private function getNearbyResults( $count, $greater = true ) {
		$options = new SMWRequestOptions();
		$options->limit = $this->limit + 1;
		$options->sort = true;

		// Note: printrequests change the caption of properties they get (they expect properties to be given to them)
		// Since we want to continue using the property for our purposes, we give a clone to the print request.
		$printrequest = new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, '', clone $this->property );

		$params = array();
		$params['format'] = 'ul';
		$params['sort'] = $this->propertystring;
		$params['order'] = 'DESC';
		if ( $greater ) $params['order'] = 'ASC';
		$cmp = "<";
		if ( $greater ) $cmp = ">";
		$querystring = "[[" . $this->propertystring . "::" . $cmp . $this->valuestring . "]]";
		$queryobj = SMWQueryProcessor::createQuery( $querystring, $params, SMWQueryProcessor::SPECIAL_PAGE, 'ul', array( $printrequest ) );
		$queryobj->querymode = SMWQuery::MODE_INSTANCES;
		$queryobj->setLimit( $this->limit );
		$queryobj->setOffset( $count );

		$results = smwfGetStore()->getQueryResult( $queryobj );

		$result = $results->getNext();
		$ret = array();
		while ( $result ) {
			array_push( $ret, array( $result[0]->getNextObject(), $result[1]->getNextObject() ) );
			$result = $results->getNext();
		}
		if ( !$greater ) {
			$temp = array();
			while ( $ret ) array_push( $temp, array_pop( $ret ) );
			$ret = $temp;
		}
		return $ret;
	}

	/**
	 * Creates the HTML for the query form for this special page.
	 *
	 * @return string  HTML for the query form
	 */
	private function queryForm() {
		$spectitle = Title::makeTitle( NS_SPECIAL, 'SearchByProperty' );
		$html  = '<form name="searchbyproperty" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg( 'smw_sbv_property' ) . ' <input type="text" name="property" value="' . htmlspecialchars( $this->propertystring ) . '" />' . "&nbsp;&nbsp;&nbsp;\n";
		$html .= wfMsg( 'smw_sbv_value' ) . ' <input type="text" name="value" value="' . htmlspecialchars( $this->valuestring ) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg( 'smw_sbv_submit' ) . "\"/>\n</form>\n";

		return $html;
	}

}
