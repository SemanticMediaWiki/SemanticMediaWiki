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
 * @author Markus Kroetzsch
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
	private $propertystring;
	/// SMWPropertyValue  The property that is searched for
	private $property;
	/// string  Name of the value that is searched for
	private $valuestring;
	/// SMWDataValue  The value that is searched for
	private $value;
	/// How many results should be displayed
	private $limit;
	/// At what position are we currently
	private $offset;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'SearchByProperty' );
	}

	/**
	 * Main entry point for the special page.
	 *
	 * @param string $query Given by MediaWiki
	 */
	public function execute( $query ) {
		global $wgOut;

		$this->setHeaders();
		$this->processParameters( $query );
		$wgOut->addHTML( $this->displaySearchByProperty() );
		$wgOut->addHTML( $this->queryForm() );

		// push locally collected output data to the output
		SMWOutputs::commitToOutputPage( $wgOut );
	}

	/**
	 * Read and interpret the given parameters.
	 *
	 * @since 1.8
	 * @param string $query from the web request as given by MW
	 */
	protected function processParameters( $query ) {
		global $wgRequest;
		// get the GET parameters
		$params = SMWInfolink::decodeParameters( $query, false );
		reset( $params );

		$inputPropertyString = $wgRequest->getText( 'property', current( $params ) );

		$inputValueString = $wgRequest->getText( 'value', next( $params ) );
		$inputValueString = str_replace( '&nbsp;', ' ', $inputValueString );
		$inputValueString = str_replace( '&#160;', ' ', $inputValueString );

		$this->property = SMWPropertyValue::makeUserProperty( $inputPropertyString );
		if ( !$this->property->isValid() ) {
			$this->propertystring = $inputPropertyString;
			$this->value = null;
			$this->valuestring = $inputValueString;
		} else {
			$this->propertystring = $this->property->getWikiValue();
			$this->value = SMWDataValueFactory::newPropertyObjectValue(
						$this->property->getDataItem(),
						$inputValueString
					);
			$this->valuestring = $this->value->isValid() ?
						$this->value->getWikiValue() :
						$inputValueString;
		}

		$limitString = $wgRequest->getVal( 'limit' );
		if ( is_numeric( $limitString ) ) {
			$this->limit = intval( $limitString );
		} else {
			$this->limit = 20;
		}

		$offsetString = $wgRequest->getVal( 'offset' );
		if ( is_numeric( $offsetString ) ) {
			$this->offset = intval( $offsetString );
		} else {
			$this->offset = 0;
		}
	}

	/**
	 * Returns the HTML for the complete search by property.
	 *
	 * @todo I18N: some parentheses hardcoded
	 * @return string  HTML of the search by property function
	 */
	protected function displaySearchByProperty() {
		global $wgOut, $smwgSearchByPropertyFuzzy;
		$linker = smwfGetLinker();

		if ( $this->propertystring === '' ) {
			return '<p>' . wfMessage( 'smw_sbv_docu' )->text() . "</p>\n";
		}

		if ( is_null( $this->value ) || !$this->value->isValid() ) {
			return '<p>' . wfMessage( 'smw_sbv_novalue', $this->property->getShortHTMLText( $linker ) )->text() . "</p>\n";
		}

		$wgOut->setPagetitle( $this->propertystring . ' ' .
			$this->value->getShortHTMLText( null ) );

		$html = '';

		$exactResults = $this->getExactResults();
		$exactCount = count( $exactResults );

		if ( $exactCount < ( $this->limit / 3 ) &&
			$this->value->isNumeric() && $smwgSearchByPropertyFuzzy ) {

			$greaterResults = $this->getNearbyResults( $exactCount, true );
			$smallerResults = $this->getNearbyResults( $exactCount, false );

			// Calculate how many greater and smaller results should be displayed
			$greaterCount = count( $greaterResults );
			$smallerCount = count( $smallerResults );

			if ( ( $greaterCount + $smallerCount + $exactCount ) > $this->limit ) {
				$lhalf = round( ( $this->limit - $exactCount ) / 2 );

				if ( $lhalf < $greaterCount ) {
					if ( $lhalf < $smallerCount ) {
						$smallerCount = $lhalf;
						$greaterCount = $lhalf;
					} else {
						$greaterCount = $this->limit - ( $exactCount + $smallerCount );
					}
				} else {
					$smallerCount = $this->limit - ( $exactCount + $greaterCount );
				}
			}

			if ( ( $greaterCount + $smallerCount + $exactCount ) == 0 ) {
				$html .= wfMessage( 'smw_result_noresults' )->text();
			} else {
				$html .= wfMessage( 'smw_sbv_displayresultfuzzy', $this->property->getShortHTMLText( $linker ), $this->value->getShortHTMLText( $linker ) )->text() . "<br />\n";
				$html .= $this->getResultList( $smallerResults, $smallerCount, false );

				if ( $exactCount == 0 ) {
					//TODO i18n: Hardcoded parentheses.
					$html .= " &#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;<em><strong><small>(" . $this->value->getLongHTMLText() . ")</small></strong></em>\n";
				} else {
					$html .= $this->getResultList( $exactResults, $exactCount, true, true );
				}

				$html .= $this->getResultList( $greaterResults, $greaterCount, true );
			}
		} else {
			$html .= wfMessage( 'smw_sbv_displayresult', $this->property->getShortHTMLText( $linker ), $this->value->getShortHTMLText( $linker ) )->text() . "<br />\n";

			if ( $exactCount == 0 ) {
				$html .= wfMessage( 'smw_result_noresults' )->text();
			} else {
				if ( $this->offset > 0 || $exactCount > $this->limit ) {
					$navi = $this->getNavigationBar( $exactCount );
				} else {
					$navi = '';
				}
				$html .= $navi . $this->getResultList( $exactResults, $this->limit, true ) . $navi;
			}
		}

		$html .= '<p>&#160;</p>';

		return $html;
	}

	/**
	 * Creates the HTML for a bullet list with all the results of the set
	 * query. Values can be highlighted to show exact matches among nearby
	 * ones.
	 *
	 * @todo I18N: some parentheses hardcoded
	 *
	 * @since 1.8 (was private displayResults before)
	 * @param array $results (array of (array of one or two SMWDataValues))
	 * @param integer $number How many results should be displayed? -1 for all
	 * @param boolean $first If less results should be displayed than
	 * 	given, should they show the first $number results, or the last
	 * 	$number results?
	 * @param boolean $highlight Should the results be highlighted?
	 *
	 * @return string  HTML with the bullet list, including header
	 */
	protected function getResultList( $results, $number, $first, $highlight = false ) {
		if ( $number > 0 ) {
			$results = $first ?
				array_slice( $results, 0 , $number ) :
				array_slice( $results, $number );
		}

		$html  = '';
		foreach ( $results as $result ) {
			$listitem = $result[0]->getLongHTMLText( smwfGetLinker() );

			// Add browsing link for wikipage results
			// Note: non-wikipage results are possible using inverse properties
			if ( $result[0]->getTypeID() == '_wpg' ) {
				$listitem .= '&#160;&#160;' . SMWInfolink::newBrowsingLink( '+', $result[0]->getLongWikiText() )->getHTML( smwfGetLinker() );
			}

			// Show value if not equal to the value that was searched
			// or if the current results are to be highlighted:
			if ( array_key_exists( 1, $result ) &&
				( $result[1] instanceof SMWDataValue ) &&
				( !$this->value->getDataItem()->equals( $result[1]->getDataItem() )
					|| $highlight ) ) {
				// TODO i18n: Hardcoded parentheses
				$listitem .= " <em><small>(" .
					$result[1]->getLongHTMLText( smwfGetLinker() ) .
					")</small></em>";
			}

			// Highlight values
			if ( $highlight ) {
				$listitem = "<strong>$listitem</strong>";
			}

			$html .= "<li>$listitem</li>\n";
		}

		return "<ul>\n$html</ul>\n";
	}

	/**
	 * Creates the HTML for a navigation bar to show long lists of results.
	 * Most of the parameters are taken from the object members.
	 *
	 * @todo I18N: message patchworking
	 *
	 * @param integer $count How many results are currently displayed?
	 * @return string  HTML with the navigation bar
	 */
	protected function getNavigationBar( $count ) {
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
				wfMessage( 'smw_result_prev' )->text()
			);
		}
		else {
			$navigation = wfMessage( 'smw_result_prev' )->text();
		}

		// TODO i18n: patchwork messages
		$navigation .=
			'&#160;&#160;&#160;&#160; <b>' .
				wfMessage( 'smw_result_results' )->text() . ' ' .
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
				wfMessage( 'smw_result_next' )->text()
			);
		} else {
			$navigation .= wfMessage( 'smw_result_next' )->text();
		}

		// Find out which limit values to offer for navigation
		$limits = array();
		foreach ( array( 20, 50, 100, 250, 500 ) as $l ) {
			if ( $l < $smwgQMaxInlineLimit ) {
				$limits[] = $l;
			} else {
				$limits[] = $smwgQMaxInlineLimit;
				break;
			}
		}

		$first = true;

		foreach ( $limits as $l ) {
			if ( $first ) {
				$navigation .= '&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;(';
				$first = false;
			} else {
				$navigation .= ' ' . wfMessage( 'pipe-separator' )->escaped() . ' ';
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
	 * @return array of array(SMWWikiPageValue, SMWDataValue) with the
	 * first being the entity, and the second the value
	 */
	protected function getExactResults() {
		$options = new SMWRequestOptions();
		$options->limit = $this->limit + 1;
		$options->offset = $this->offset;
		$options->sort = true;

		$res = smwfGetStore()->getPropertySubjects(
			$this->property->getDataItem(),
			$this->value->getDataItem(),
			$options );

		$results = array();
		foreach ( $res as $result ) {
			$results[] = array(
					SMWDataValueFactory::newDataItemValue( $result, null ),
					$this->value
				);
		}

		return $results;
	}

	/**
	 * Returns all results that have a value near to the searched for value
	 * on the property, ordered, and sorted by ending with the smallest
	 * one.
	 *
	 * @param integer $count How many entities have the exact same value on the property?
	 * @param integer $greater Should the values be bigger? Set false for smaller values.
	 *
	 * @return array of array of SMWWikiPageValue, SMWDataValue with the
	 * first being the entity, and the second the value
	 */
	protected function getNearbyResults( $count, $greater = true ) {
		$valueDescription = new SMWValueDescription(
			$this->value->getDataItem(),
			$this->property->getDataItem(),
			$greater ? SMW_CMP_GRTR : SMW_CMP_LESS
		);
		$someProperty = new SMWSomeProperty(
			$this->property->getDataItem(),
			$valueDescription
		);
		$query = new SMWQuery( $someProperty );
		$query->setLimit( $this->limit );
		$query->sort = true;
		$query->sortkeys = array(
			$this->property->getDataItem()->getKey() =>
			( $greater ? 'ASC' : 'DESC' )
		);

		// Note: printrequests change the caption of properties they
		// get (they expect properties to be given to them).
		// Since we want to continue using the property for our
		// purposes, we give a clone to the print request.
		$printouts = array(
			new SMWPrintRequest( SMWPrintRequest::PRINT_THIS, '' ),
			new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, '', clone $this->property )
		);
		$query->setExtraPrintouts( $printouts );

		$queryResults = smwfGetStore()->getQueryResult( $query );

		$result = array();
		while ( $resultArrays = $queryResults->getNext() ) {
			$r = array();
			foreach ( $resultArrays as $resultArray ) {
				$r[] = $resultArray->getNextDataValue();
			}
			// Note: if results have multiple values for the property
			// then this code just pick the first, which may not be
			// the reason why the result is shown here, i.e., it could
			// be out of order.
			$result[] = $r;
		}

		if ( !$greater ) {
			$result = array_reverse( $result );
		}

		return $result;
	}

	/**
	 * Creates the HTML for the query form for this special page.
	 *
	 * @return string  HTML for the query form
	 */
	protected function queryForm() {
		SMWOutputs::requireResource( 'ext.smw.property' );
		$spectitle = SpecialPage::getTitleFor( 'SearchByProperty' );

		return '<form name="searchbyproperty" action="' .
				htmlspecialchars( $spectitle->getLocalURL() ) .
			'" method="get">' . "\n" .
			'<input type="hidden" name="title" value="' .
				$spectitle->getPrefixedText() . '"/>' .
			wfMessage( 'smw_sbv_property' )->text() .
			' <input type="text" id="property_box" name="property" value="' .
				htmlspecialchars( $this->propertystring ) .
			"\" />&#160;&#160;&#160;\n" .
			wfMessage( 'smw_sbv_value' )->text() .
			' <input type="text" name="value" value="' .
				htmlspecialchars( $this->valuestring ) .
			"\" />\n" .
			'<input type="submit" value="' .
				wfMessage( 'smw_sbv_submit' )->text() .
			"\"/>\n</form>\n";
	}
}