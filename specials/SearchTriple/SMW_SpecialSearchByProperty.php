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
	private $propertystring = '';
	/// SMWPropertyValue  The property that is searched for
	private $property = null;
	/// string  Name of the value that is searched for
	private $valuestring = '';
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
		$this->propertystring = $wgRequest->getText( 'property' );
		$this->valuestring = $wgRequest->getText( 'value' );

		$params = SMWInfolink::decodeParameters( $query, false );
		reset( $params );

		// no GET parameters? Then try the URL
		if ( $this->propertystring === '' ) $this->propertystring = current( $params );
		if ( $this->valuestring === '' ) $this->valuestring = next( $params );

		$this->valuestring = str_replace( '&nbsp;', ' ', $this->valuestring );
		$this->valuestring = str_replace( '&#160;', ' ', $this->valuestring );

		$this->property = SMWPropertyValue::makeUserProperty( $this->propertystring );
		if ( !$this->property->isValid() ) {
			$this->propertystring = '';
		} else {
			$this->propertystring = $this->property->getWikiValue();
			$this->value = SMWDataValueFactory::newPropertyObjectValue( $this->property->getDataItem(), $this->valuestring );

			if ( $this->value->isValid() ) {
				$this->valuestring = $this->value->getWikiValue();
			} else {
				$this->value = null;
			}
		}

		$limitstring = $wgRequest->getVal( 'limit' );
		if ( is_numeric( $limitstring ) ) {
			$this->limit =  intval( $limitstring );
		}

		$offsetstring = $wgRequest->getVal( 'offset' );
		if ( is_numeric( $offsetstring ) ) {
			$this->offset = intval( $offsetstring );
		}

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
		global $wgOut, $smwgSearchByPropertyFuzzy;
		$linker = smwfGetLinker();

		if ( $this->propertystring === '' ) {
			return '<p>' . wfMsg( 'smw_sbv_docu' ) . "</p>\n";
		}

		if ( ( $this->value == null ) || !$this->value->isValid() ) {
			return '<p>' . wfMsg( 'smw_sbv_novalue', $this->property->getShortHTMLText( $linker ) ) . "</p>\n";
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
				$html .= wfMsg( 'smw_sbv_displayresultfuzzy', $this->property->getShortHTMLText( $linker ), $this->value->getShortHTMLText( $linker ) ) . "<br />\n";
				$html .= $this->displayResults( $lesser, $cL, false );

				if ( $count == 0 ) {
					$html .= " &#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;<em><strong><small>(" . $this->value->getLongHTMLText() . ")</small></strong></em>\n";
				} else {
					$html .= $this->displayResults( $exact, $count, true, true );
				}

				$html .= $this->displayResults( $greater, $cG );
			}
		} else {
			$html .= wfMsg( 'smw_sbv_displayresult', $this->property->getShortHTMLText( $linker ), $this->value->getShortHTMLText( $linker ) ) . "<br />\n";

			if ( 0 == $count ) {
				$html .= wfMsg( 'smw_result_noresults' );
			} else {
				$navi = $this->getNavigationBar( $count );
				if ( ( $this->offset > 0 ) || ( $count > $this->limit ) ) $html .= $navi;
				$html .= $this->displayResults( $exact, $this->limit );
				if ( ( $this->offset > 0 ) || ( $count > $this->limit ) ) $html .= $navi;
			}
		}

		$html .= '<p>&#160;</p>';

		return $html;
	}

	/**
	 * Creates the HTML for a bullet list with all the results of the set query.
	 *
	 * @param[in] $results array of array of SMWWikiPageValue, SMWDataValue  The entity and its datavalue
	 * @param[in] $number int  How many results should be displayed? -1 for all
	 * @param[in] $first bool  If less results should be displayed than given, should they show the first $number results, or the last $number results?
	 * @param[in] $highlight bool  Should the results be highlighted?
	 *
	 * @return string  HTML with the bullet list and a header
	 */
	private function displayResults( $results, $number = - 1, $first = true, $highlight = false ) {
		$html  = "<ul>\n";

		if ( !$first && ( $number > 0 ) ) {
			// TODO: why is this reversed?
			// I (jeroendedauw) replaced a loop using array_shift by this, which is equivalent.
			$results = array_slice( array_reverse( $results ), 0, $number );
		}

		while ( $results && $number != 0 ) {
			$result = array_shift( $results );

			$html .= '<li>' . $result[0]->getLongHTMLText( smwfGetLinker() );

			if ( $result[0]->getTypeID() == '_wpg' ) {
				$html .= '&#160;&#160;' . SMWInfolink::newBrowsingLink( '+', $result[0]->getLongWikiText() )->getHTML( smwfGetLinker() );
			}

			if ( array_key_exists( 1, $result ) && is_object( $result[1] ) && ( ( $this->value != $result[1] ) || $highlight ) ) {
				$html .= " <em><small>(" . $result[1]->getLongHTMLText( smwfGetLinker() ) . ")</small></em>";
			}

			$html .= "</li>";

			if ( $highlight ) {
				$html = "<strong>" . $html . "</strong>";
			}

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
		global $smwgQMaxInlineLimit;

		if ( $this->offset > 0 ) {
			$navigation = Html::element(
				'a',
				array(
					'href' => SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL( array(
						'offset' => max( 0, $this->offset - $this->limit ),
						'limit' => $this->limit,
						'property' => $this->property->getWikiValue(),
						'value' => $this->value->getWikiValue()
					) )
				),
				wfMsg( 'smw_result_prev' )
			);
		}
		else {
			$navigation = wfMsg( 'smw_result_prev' );
		}

		$navigation .=
			'&#160;&#160;&#160;&#160; <b>' .
				wfMsg( 'smw_result_results' ) . ' ' .
				( $this->offset + 1 ) . '– ' .
				( $this->offset + min( $count, $this->limit ) ) .
			'</b>&#160;&#160;&#160;&#160;';

		if ( $count > $this->limit ) {
			$navigation .= Html::element(
				'a',
				array(
					'href' => SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL( array(
						'offset' => $this->offset + $this->limit,
						'limit' => $this->limit,
						'property' => $this->property->getWikiValue(),
						'value' => $this->value->getWikiValue()
					) )
				),
				wfMsg( 'smw_result_next' )
			);
		} else {
			$navigation .= wfMsg( 'smw_result_next' );
		}

		$max = false;
		$first = true;

		foreach ( array( 20, 50, 100, 250, 500 ) as $l ) {
			if ( $max ) continue;

			if ( $first ) {
				$navigation .= '&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;(';
				$first = false;
			} else {
				$navigation .= ' ' . wfMsgExt( 'pipe-separator' , 'escapenoentities' ) . ' ';
			}

			if ( $l > $smwgQMaxInlineLimit ) {
				$l = $smwgQMaxInlineLimit;
				$max = true;
			}

			if ( $this->limit != $l ) {
				$navigation .= Html::element(
					'a',
					array(
						'href' => SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL( array(
							'offset' => $this->offset,
							'limit' => $l,
							'property' => $this->property->getWikiValue(),
							'value' => $this->value->getWikiValue()
						) )
					),
					$l
				);
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

		$res = smwfGetStore()->getPropertySubjects( $this->property->getDataItem(), $this->value->getDataItem(), $options );
		$results = array();

		foreach ( $res as $result ) {
			array_push( $results, array(
				SMWDataValueFactory::newDataItemValue( $result, null ),
				$this->value
			) );
		}

		return $results;
	}

	/**
	 * Returns all results that have a value near to the searched for value
	 * on the property, ordered, and sorted by ending with the smallest one.
	 *
	 * @param[in] $count int  How many entities have the exact same value on the property?
	 * @param[in] $greater bool  Should the values be bigger? Set false for smaller values
	 *
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
		$cmp = '<';
		if ( $greater ) $cmp = '>';

		$querystring = '[[' . $this->propertystring . '::' . $cmp . $this->valuestring . ']]';
		$params = SMWQueryProcessor::getProcessedParams( $params, array( $printrequest ) );
		$queryobj = SMWQueryProcessor::createQuery( $querystring, $params, SMWQueryProcessor::SPECIAL_PAGE, 'ul', array( $printrequest ) );
		$queryobj->querymode = SMWQuery::MODE_INSTANCES;
		$queryobj->setLimit( $this->limit );
		$queryobj->setOffset( $count );

		$results = smwfGetStore()->getQueryResult( $queryobj );

		$result = $results->getNext();
		$ret = array();

		while ( $result ) {
			$r = array( $result[0]->getNextDataValue() );
			
			if ( array_key_exists( 1, $result ) ) {
				$r[] = $result[1]->getNextDataValue();
			}
			
			$ret[] = $r;
			
			$result = $results->getNext();
		}

		if ( !$greater ) {
			$ret = array_reverse( $ret );
		}

		return $ret;
	}

	/**
	 * Creates the HTML for the query form for this special page.
	 *
	 * @return string  HTML for the query form
	 */
	private function queryForm() {
		self::addAutoComplete();
		$spectitle = SpecialPage::getTitleFor( 'SearchByProperty' );
		$html  = '<form name="searchbyproperty" action="' . htmlspecialchars( $spectitle->getLocalURL() ) . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg( 'smw_sbv_property' ) . ' <input type="text" id="property_box" name="property" value="' . htmlspecialchars( $this->propertystring ) . '" />' . "&#160;&#160;&#160;\n";
		$html .= wfMsg( 'smw_sbv_value' ) . ' <input type="text" name="value" value="' . htmlspecialchars( $this->valuestring ) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg( 'smw_sbv_submit' ) . "\"/>\n</form>\n";

		return $html;
	}

	/**
	 * Creates the JS needed for adding auto-completion to queryForm(). Uses the
	 * MW API to fetch suggestions.
	 *
	 */
	protected static function addAutoComplete() {
		SMWOutputs::requireResource( 'jquery.ui.autocomplete' );

		$javascript_autocomplete_text = <<<END
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery("#property_box").autocomplete({
		minLength: 2,
		source: function(request, response) {
			jQuery.getJSON(wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['property']+'&format=jsonfm&search='+request.term, function(data){
				//remove the word 'Property:' from returned data
				for(i=0;i<data[1].length;i++) data[1][i]=data[1][i].substr(data[1][i].indexOf(':')+1);
				response(data[1]);
			});
		}
	});
});
</script>

END;

		SMWOutputs::requireScript( 'smwAutocompleteSpecialSearchByProperty', $javascript_autocomplete_text );
	}

}
