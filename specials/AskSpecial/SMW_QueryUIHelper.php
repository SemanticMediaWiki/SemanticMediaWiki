<?php

/**
 * A base class for Semantic Search UIs. All Semantic Search UI's may subclass
 * from this.
 *
 * The commonly used and overloaded methods are the ones which create some default
 * UI elements (the getxxxFormBox() methods) and corresponding methods that
 * extract data from them (the processxxxFormBox() methods).
 *
 * @author Markus Krötzsch
 * @author Yaron Koren
 * @author Sanyam Goyal
 * @author Jeroen De Dauw
 * @author Devayon Das
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
abstract class SMWQueryUI extends SpecialPage {
	/**
	 * The handle for the underlying SMWQueryUIHelper class.
	 * @var SMWQueryUIHelper
	 * @see SMWQueryUIHelper
	 */
	protected $uiCore;

	/**
	 * Is auto-complete enabled for these UI elements?
	 *
	 * @var mixed SMWQUeryUI::ENABLE_AUTO_SUGGEST | SMWQUeryUI::DISABLE_AUTO_SUGGEST
	 */
	private $autoCompleteEnabled = false;

	const ENABLE_AUTO_SUGGEST = true;
	const DISABLE_AUTO_SUGGEST = false;

	/**
	 * Initialises the page. Sets the property $uiCore to the appropriate helper
	 * object.
	 *
	 * To create a custom UI, adding changes to makePage() is usually enough,
	 * but one might want to overload this method to get better handling of form
	 * parameters.
	 *
	 * @global OutputPage $wgOut
	 * @global WebRequest $wgRequest
	 * @global boolean $smwgQEnabled
	 * @param string $p the sub-page string
	 */
	public function execute( $p ) {
		global $wgOut, $wgRequest, $smwgQEnabled, $wgFeedClasses;

		$this->setHeaders();

		if ( !$smwgQEnabled ) {
			$wgOut->addHTML( '<br />' . wfMsg( 'smw_iq_disabled' ) );
		} else {
			$format_options_requested = $this->processFormatOptions( $wgRequest ); // handling ajax for format options
			if ( !$format_options_requested ) {
				// Checking if a query string has been sent by using the form
				if ( !( $this->processQueryFormBox( $wgRequest ) === false ) ) {
					$params = $this->processParams();
					$this->uiCore =  SMWQueryUIHelper::makeForUI(
							$this->processQueryFormBox( $wgRequest ),
							$params,
							array(),
							false );
					if ( $this->uiCore->getQueryString() != "" ) {
						$this->uiCore->execute( $p );
					}
				} else {
				// the user has entered this page from a wiki-page using an infolink,
				// or no query has been set
					$this->uiCore =  SMWQueryUIHelper::makeForInfoLink( $p );
				}
				// adding rss feed of results to the page head
				if ( ( $this->isSyndicated() )
						&& ( $this->uiCore->getQueryString() !== '' )
						&& ( method_exists( $wgOut, 'addFeedlink' ) ) // remove this line after MW 1.5 is no longer supported by SMW
						&& ( array_key_exists( 'rss', $wgFeedClasses ) ) ) {
					$res = $this->uiCore->getResultObject();
					$link = $res->getQueryLink();
					$link->setParameter( 'rss', 'format' );
					$link->setParameter( $this->uiCore->getLimit(), 'limit' );
					$wgOut->addFeedLink( 'rss', $link->getURl() );
				}

				$this->makePage( $p );
			}
		}

		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
	}

	/**
	 * This method should call the various processXXXBox() methods for each of
	 * the corresponding getXXXBox() methods which the UI uses.
	 * Merge the results of these methods and return them.
	 *
	 * @global WebRequest $wgRequest
	 * @return array
	 */
	protected abstract function processParams();

	/**
	 * To enable/disable syndicated feeds of results to appear in the UI header
	 *
	 * @return boolean
	 */
	public function isSyndicated() {
		return true;
	}

	/**
	 * The main entrypoint for your UI. Call the various methods of SMWQueryUI
	 * and SMWQueryUIHelper to build ui elements and to process them.
	 */
	protected abstract function makePage( $p );

	/**
	 * Builds a read-only #ask embed code of the given query. The code is
	 * presented in html code.
	 *
	 * @return string
	 */
	protected function getAskEmbedBox() {
		$result = '';
		if ( $this->uiCore->getQueryString() != "" ) {
			$result = Html::rawElement( 'div', array( 'id' => 'inlinequeryembed' ),
				Html::rawElement( 'div', array( 'id' => 'inlinequeryembedinstruct' ), wfMsg( 'smw_ask_embed_instr' ) ) .
				Html::element( 'textarea', array( 'id' => 'inlinequeryembedarea', 'readonly' => 'yes', 'cols' => '20', 'rows' => '6', 'onclick' => 'this.select()' ),
			$this->uiCore->getAsk() ) );
		}
		return $result;
	}

	/**
	 * A helper function to enable JQuery
	 *
	 * @global OutputPage $wgOut
	 * @global boolean $smwgJQueryIncluded
	 */
	protected function enableJQuery() {
		global $wgOut, $smwgJQueryIncluded, $smwgScriptPath;
			if ( !$smwgJQueryIncluded ) {
				$realFunction = array( 'OutputPage', 'includeJQuery' );
				if ( is_callable( $realFunction ) ) {
					$wgOut->includeJQuery();
				} else {
					$wgOut->addScriptFile( "$smwgScriptPath/libs/jquery-1.4.2.min.js" );
				}

				$smwgJQueryIncluded = true;
			}
	}

	/**
	 * A helper function to enable JQueryUI
	 * @global OutputPage $wgOut
	 * @global string $smwgScriptPath
	 * @global boolean $smwgJQueryUIIncluded
	 */
	protected function enableJQueryUI() {
		global $wgOut, $smwgScriptPath, $smwgJQueryUIIncluded;

		$wgOut->addExtensionStyle( "$smwgScriptPath/skins/jquery-ui/base/jquery.ui.all.css" );

			$this-> enableJQuery();

			$scripts = array();

			if ( !$smwgJQueryUIIncluded ) {
				$scripts[] = "$smwgScriptPath/libs/jquery-ui/jquery.ui.core.min.js";
				$scripts[] = "$smwgScriptPath/libs/jquery-ui/jquery.ui.widget.min.js";
				$scripts[] = "$smwgScriptPath/libs/jquery-ui/jquery.ui.position.min.js";
				$scripts[] = "$smwgScriptPath/libs/jquery-ui/jquery.ui.autocomplete.min.js";
				$smwgJQueryUIIncluded = true;
			}

			foreach ( $scripts as $js ) {
				$wgOut->addScriptFile( $js );
			}
	}

	/**
	 * Adds common JS and CSS required for Autocompletion.
	 * @global OutputPage $wgOut
	 */
	protected function addAutocompletionJavascriptAndCSS() {
		global $wgOut;
		if ( $this->autoCompleteEnabled == false ) {
			$this->enableJQueryUI();
			$javascriptAutocompleteText = <<<END
<script type="text/javascript">
function smw_split(val) {
	return val.split('\\n');
}
function smw_extractLast(term) {
	return smw_split(term).pop();
}
function smw_escapeQuestion(term){
	if (term.substring(0, 1) == "?") {
		return term.substring(1);
	} else {
		return term;
	}
}

jQuery.noConflict();
/* extending jQuery functions for custom highligting */
jQuery.ui.autocomplete.prototype._renderItem = function( ul, item) {
	var term_without_q = smw_escapeQuestion(smw_extractLast(this.term));
	var re = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term_without_q.replace("/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi", "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi");
	var loc = item.label.search(re);
	if (loc >= 0) {
		var t = item.label.substr(0, loc) + '<strong>' + item.label.substr(loc, term_without_q.length) + '</strong>' + item.label.substr(loc + term_without_q.length);
	} else {
		var t = item.label;
	}
	jQuery( "<li></li>" )
		.data( "item.autocomplete", item )
		.append( " <a>" + t + "</a>" )
		.appendTo( ul );
};

///* extending jquery functions for custom autocomplete matching */
jQuery.extend( jQuery.ui.autocomplete, {
	filter: function(array, term) {
		var matcher = new RegExp("\\\b" + jQuery.ui.autocomplete.escapeRegex(term), "i" );
		return jQuery.grep( array, function(value) {
			return matcher.test( value.label || value.value || value );
		});
	}
});
</script>
END;

			$wgOut->addScript( $javascriptAutocompleteText );
			$this->autoCompleteEnabled = true;
		}
	}

	/**
	 * Build the navigation bar for some given query result.
	 *
	 * UI may overload this for a different layout. The navigation bar can
	 * be hidden by overloading usesNavigationBar(). To change the url format,
	 * one may overload getUrlTail();
	 *
	 * @global int $smwgQMaxInlineLimit
	 * @global Language $wgLang
	 * @param int $limit
	 * @param int $offset
	 * @param boolean $hasFurtherResults
	 *
	 * @return string
	 */
	public function getNavigationBar( $limit, $offset, $hasFurtherResults ) {
		global $smwgQMaxInlineLimit, $wgLang;
		$urlTail = $this->getUrlTail();
		// Prepare navigation bar.
		if ( $offset > 0 ) {
			$navigation = Html::element(
				'a',
				array(
					'href' => $this->getTitle()->getLocalURL(
						'offset=' . max( 0, $offset - $limit ) .
						'&limit=' . $limit . $urlTail
					),
					'rel' => 'nofollow'
				),
				wfMsg( 'smw_result_prev' )
			);

		} else {
			$navigation = wfMsg( 'smw_result_prev' );
		}

		$navigation .=
			'&#160;&#160;&#160;&#160; <b>' .
				wfMsg( 'smw_result_results' ) . ' ' . $wgLang->formatNum( $offset + 1 ) .
			' - ' .
				$wgLang->formatNum( $offset + $this->uiCore->getResultCount() ) .
			'</b>&#160;&#160;&#160;&#160;';

		if ( $hasFurtherResults ) {
			$navigation .= Html::element(
				'a',
				array(
					'href' => $this->getTitle()->getLocalURL(
						'offset=' . ( $offset + $limit ) .
						'&limit=' . $limit . $urlTail
					),
					'rel' => 'nofollow'
				),
				wfMsg( 'smw_result_next' )
			);
		} else {
			$navigation .= wfMsg( 'smw_result_next' );
		}

		$first = true;

		foreach ( array( 20, 50, 100, 250, 500 ) as $l ) {
			if ( $l > $smwgQMaxInlineLimit ) break;

			if ( $first ) {
				$navigation .= '&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;(';
				$first = false;
			} else {
				$navigation .= ' | ';
			}

			if ( $limit != $l ) {
				$navigation .= Html::element(
					'a',
					array(
						'href' => $this->getTitle()->getLocalURL(
							'offset=' . $offset .
							'&limit=' . $l . $urlTail
						),
						'rel' => 'nofollow'
					),
					$wgLang->formatNum( $l, false )
				);
			} else {
				$navigation .= '<b>' . $wgLang->formatNum( $l ) . '</b>';
			}
		}

		$navigation .= ')';

		return $navigation;
	}

	/**
	 * Generates the form element(s) for the Query-string.  Use its
	 * complement processQueryFormBox() to decode data sent through these elements.
	 * UI's may overload both to change form parameters.
	 *
	 * @global OutputPage $wgOut
	 * @global string $smwgScriptPath
	 * @return string
	 */
	protected function getQueryFormBox() {
		global $wgOut, $smwgScriptPath;
		$result = '<div>';
		$result .= Html::element( 'textarea', array( 'name' => 'q', 'id' => 'querybox' ),
					$this->uiCore->getQueryString() );
		$result .= '</div>';
		$this->enableJQuery();
		$wgOut->addScriptFile( "$smwgScriptPath/skins/elastic/jquery.elastic.source.js" );

		/*
		 * Compatibity function for disabling elastic textboxes for IE. This may
		 * be removed when jQuery 1.4 or above is supported.
		 */
		$javascript = <<<EOT
jQuery(document).ready(function(){
		if(jQuery.browser.msie){
			jQuery('#querybox').attr('rows',5);
		} else {
			jQuery('#querybox').elastic();
			jQuery('#querybox').trigger('update');
		}
});
EOT;
		$wgOut->addInlineScript( $javascript );

		// TODO:enable/disable on checking for errors; perhaps show error messages right below the box
		return $result;
	}

	/**
	 * Decodes form data sent through form-elements generated by
	 * its complement, getQueryFormBox. UIs may overload both to change form
	 * parameters.
	 *
	 * @param WebRequest $wgRequest
	 * @return mixed returns the querystring if possible, false if no querystring is set
	 */
	protected function processQueryFormBox( WebRequest $wgRequest ) {
		if ( $wgRequest->getCheck( 'q' ) ) {
			$query = $wgRequest->getVal( 'q' );
			return $query;
		} else {
			return false;
		}
	}

	/**
	 * Decodes printouts and sorting - related form options generated by its
	 * complement, getPoSortFormBox(). UIs may overload both to change form
	 * parameters.
	 *
	 * @global boolean $smwgQSortingSupport
	 * @global Language $wgContLang
	 * @param WebRequest $wgRequest
	 * @return string
	 */
	protected function processPoSortFormBox( WebRequest $wgRequest ) {
		global $smwgQSortingSupport, $wgContLang;
		if ( !$smwgQSortingSupport ) return array();

		$params = array();
		// loading all values from form
		$orderValues = $wgRequest->getArray( 'order' );
		$propertyValues = $wgRequest->getArray( 'property' );
		$propertyLabelValues = $wgRequest->getArray( 'prop_label' );
		$propertyLimitValues = $wgRequest->getArray( 'prop_limit' );
		$propertyFormatValues = $wgRequest->getArray( 'prop_format' );
		$categoryValues = $wgRequest->getArray( 'category' );
		$categoryLabelValues = $wgRequest->getArray( 'cat_label' );
		$categoryYesValues = $wgRequest->getArray( 'cat_yes' );
		$categoryNoValues = $wgRequest->getArray( 'cat_no' );
		$mainColumnLabels = $wgRequest->getArray( 'maincol_label' );
		$po = array();

		// processing params for main result column
		if ( is_array( $mainColumnLabels ) ) {
			foreach ( $mainColumnLabels as $key => $label ) {
				if ( $label == '' ) {
					$po[$key] = "?";
				} else {
					$po[$key] = "? = $label";
				}
			}
		}
		// processing params for category printouts
		$categoryNamespace = $wgContLang->getNsText( NS_CATEGORY );
		if ( is_array( $categoryValues ) ) {
			foreach ( $categoryValues as $key => $value ) {
				if ( trim( $value ) == '' ) {
					$po[$key] = "?$categoryNamespace" ;
				} else {
					$po[$key] = "?$categoryNamespace:$value";
					if ( is_array( $categoryYesValues ) &&
							is_array( $categoryNoValues ) &&
							array_key_exists( $key, $categoryYesValues ) &&
							array_key_exists( $key, $categoryNoValues ) ) {

						if ( $categoryYesValues[$key] !== '' && $categoryNoValues[$key] !== '' ) {
							$po[$key] .= "#$categoryYesValues[$key],$categoryNoValues[$key]";
						}
					}
				}
			}
		}
		if ( is_array( $categoryLabelValues ) ) {
			foreach ( $categoryLabelValues as $key => $value ) {
				if ( trim( $value ) != '' ) {
				 $po[$key] .= ' = ' . $value;
				}
			}
		}
		// processing params for property printouts
		if ( is_array( $propertyValues ) ) {
			$params['sort'] = '';
			$params['order'] = '';
			foreach ( $propertyValues as $key => $propertyValue ) {
				$propertyValues[$key] = trim( $propertyValue );
				if ( $propertyValue == '' ) {
					unset( $propertyValues[$key] );
				}
				if ( is_array( $orderValues ) && array_key_exists( $key, $orderValues ) && $orderValues[$key] != 'NONE' ) {
					$params['sort'] .= ( $params['sort'] != '' ? ',':'' ) . $propertyValues[$key];
					$params['order'] .= ( $params['order'] != '' ? ',':'' ) . $orderValues[$key];
				}
			}
			if ( $params['sort'] == '' ) {
				unset ( $params['sort'] );
			}
			if ( $params['order'] == '' ) {
				unset ( $params['order'] );
			}
			$displayValues = $wgRequest->getArray( 'display' );
			if ( is_array( $displayValues ) ) {
				foreach ( $displayValues as $key => $value ) {
					if ( $value == '1' && array_key_exists( $key, $propertyValues ) ) {
					$propertyValues[$key] = '?' . trim( $propertyValues[$key] ); // adding leading ?
						if ( is_array( $propertyFormatValues ) && // adding PO format
								array_key_exists( $key, $propertyFormatValues ) &&
								$propertyFormatValues[$key] != '' ) {
							$propertyValues[$key] .= '#' . $propertyFormatValues[$key];
						}
						if ( is_array( $propertyLabelValues ) &&
								array_key_exists( $key, $propertyLabelValues ) &&
								$propertyLabelValues[$key] != '' ) { // adding label
							$propertyValues[$key] .= ' = ' . $propertyLabelValues[$key];
						}
						if ( is_array( $propertyLimitValues ) && // adding limit
								array_key_exists( $key, $propertyLimitValues ) &&
								$propertyLimitValues[$key] != '' ) {
							// / @bug limit, when specified causes incorrect ordering of printouts
							$po[] = $propertyValues[$key];
							$po[] = '+limit=' . $propertyLimitValues[$key];
						} else {
							$po[$key] = $propertyValues[$key];
						}
					}
				}
			}
		}
		ksort( $po );
		$params = array_merge( $params, $po );
		return $params;

	}

	/**
	 * Generates the forms elements(s) for choosing printouts and sorting
	 * options. Use its complement processPoSortFormBox() to decode data
	 * sent by these elements.
	 *
	 * @return string
	 */
	protected function getPoSortFormBox( $enableAutocomplete = SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
		global $smwgQSortingSupport, $wgRequest, $wgOut, $smwgScriptPath;

		if ( !$smwgQSortingSupport ) return '';
		$this->enableJQueryUI();
		$wgOut->addScriptFile( "$smwgScriptPath/libs/jquery-ui/jquery-ui.dialog.min.js" );
		$wgOut->addStyle( "$smwgScriptPath/skins/SMW_custom.css" );

		$result = '';
		$numSortValues = 0;
		// START: create form elements already submitted earlier via form
		// attempting to load parameters from $wgRequest
		$propertyValues = $wgRequest->getArray( 'property' );
		$propertyLabelValues = $wgRequest->getArray( 'prop_label' );
		$propertyFormatValues = $wgRequest->getArray( 'prop_format' );
		$propertyLimitValues = $wgRequest->getArray( 'prop_limit' );
		$orderValues = $wgRequest->getArray( 'order' );
		$displayValues = $wgRequest->getArray( 'display' );
		$categoryValues = $wgRequest->getArray( 'category' );
		$categoryLabelValues = $wgRequest->getArray( 'cat_label' );
		$categoryYesValues = $wgRequest->getArray( 'cat_yes' );
		$categoryNoValues = $wgRequest->getArray( 'cat_no' );
		$mainColumnLabels = $wgRequest->getArray( 'maincol_label' );

		if ( is_array( $propertyValues ) || is_array( $categoryValues ) || is_array( $mainColumnLabels ) ) {
			/*
			 * Printouts were set via this Ui
			 */
			if ( is_array( $propertyValues ) ) {
				// remove empty property values
				foreach ( $propertyValues as $key => $propertyValue ) {
					$propertyValues[$key] = trim( $propertyValue );
					if ( $propertyValue == '' ) {
						unset( $propertyValues[$key] );
					}
				}
			}
		} else {
			/*
			 * Printouts and sorting were set via another widget/form/source, so
			 * create elements by fetching data from $uiCore. The exact ordering
			 * of Ui elements might not be preserved, if the above block were to
			 * be removed.
			 */
			$propertyValues = array();
			$propertyLabelValues = array();
			$propertyFormatValues = array();
			$propertyLimitValues = array();
			$orderValues = array();
			$displayValues = array();
			$categoryValues = array();
			$categoryLabelValues = array();
			$categoryYesValues = array();
			$categoryNoValues = array();
			$mainColumnLabels = array();

			$params = $this->uiCore->getParameters();
			if ( array_key_exists( 'sort', $params ) && array_key_exists( 'order', $params ) ) {
				$sortVal = explode( ',', trim( strtolower( $params['sort'] ) ) );
				$orderVal = explode( ',', $params['order'] );
				reset( $sortVal );
				reset( $orderVal );
				// give up if sort and order dont have equal number of elements
				if ( count( $sortVal ) !== count( $orderVal ) ) {
					$orderVal = array();
					$sortVal = array();
				}
			} else {
				$orderVal = array();
				$sortVal = array();
			}
			$printOuts = ( $this->uiCore->getPrintOuts() );
			$counter = 0;
			foreach ( $printOuts as $poKey => $poValue ) {
				if ( $poValue->getMode() == SMWPrintRequest::PRINT_CATS ) {
					$categoryValues[$counter] = '';
					$categoryLabelValues[$counter] = $poValue->getLabel();
					$categoryYesValues[$counter] = '';
					$categoryNoValues[$counter] = '';
					$counter++;
				} elseif ( $poValue->getMode() == SMWPrintRequest::PRINT_PROP ) {
					$tempProperty = trim( strtolower( $poValue->getData()->getText() ) );
					$searchKey = array_search( $tempProperty, $sortVal );
					if ( !( $searchKey === false ) ) {
						while ( $searchKey != 0 ) {
							$propertyValues[$counter] = array_shift( $sortVal );
							$orderValues[$counter] = array_shift( $orderVal );
							$propertyLabelValues[$counter] = '';
							$propertyFormatValues[$counter] = '';
							$propertyLimitValues[$counter] = '';
							$counter++;
							$searchKey--;
						}
						$propertyValues[$counter] = $poValue->getData()->getText();
						$propertyLabelValues[$counter] = ( $poValue->getLabel() == $propertyValues[$counter] ) ? '':$poValue->getLabel();
						$propertyFormatValues[$counter] = $poValue->getOutputFormat();
						$propertyLimitValues[$counter] = $poValue->getParameter( 'limit' ) ? $poValue->getParameter( 'limit' ):'';
						$orderValues[$counter] = $orderVal[0];
						$displayValues[$counter] = '1';
						$counter++;
						array_shift( $orderVal );
						array_shift( $sortVal );
					} else {
						$propertyValues[$counter] = $poValue->getData()->getText();
						$propertyLabelValues[$counter] = ( $poValue->getLabel() == $propertyValues[$counter] ) ? '':$poValue->getLabel();
						$propertyFormatValues[$counter] = $poValue->getOutputFormat();
						$propertyLimitValues[$counter] = $poValue->getParameter( 'limit' ) ? $poValue->getParameter( 'limit' ):'';
						$displayValues[$counter] = '1';
						$counter++;
					}
				} elseif ( $poValue->getMode() == SMWPrintRequest::PRINT_THIS ) {
					$mainColumnLabels[$counter] = $poValue->getLabel();
					$counter++;
				} elseif ( $poValue->getMode() == SMWPrintRequest::PRINT_CCAT ) {
					$outputFormat = explode( ',', $poValue->getOutputFormat() );
					if ( !array_key_exists( 1, $outputFormat ) ) {
						$outputFormat[1] = '';
					}
					$categoryValues[$counter] = $poValue->getData()->getText();
					$categoryLabelValues[$counter] = $poValue->getLabel();
					$categoryYesValues[$counter] = $outputFormat[0];
					$categoryNoValues[$counter] = $outputFormat[1];
					$counter++;
				}

			}
			while ( !empty( $sortVal ) ) {
				$propertyValues[$counter] = array_shift( $sortVal );
				$orderValues[$counter] = array_shift( $orderVal );
				$propertyLabelValues[$counter] = '';
				$propertyFormatValues[$counter] = '';
				$propertyLimitValues[$counter] = '';
				$counter++;
			}
		}
		$i = 0;
		$additionalPOs = array();
		if ( is_array( $propertyValues ) ) {
			$keys = array_keys( $propertyValues );
			foreach ( $keys as $value ) {
				$additionalPOs[$value] = $propertyValues[$value]; // array_merge won't work because numeric keys need to be preserved
			}
		}
		if ( is_array( $categoryValues ) ) {// same as testing $categoryLabelValues
			$keys = array_keys( $categoryValues );
			foreach ( $keys as $value ) {
				$additionalPOs[$value] = $categoryValues[$value]; // array_merge won't work because numeric keys need to be preserved
			}
		}
		if ( is_array( $mainColumnLabels ) ) {
			$keys = array_keys( $mainColumnLabels );
			foreach ( $keys as $value ) {
				$additionalPOs[$value] = $mainColumnLabels[$value]; // array_merge won't work because numeric keys need to be preserved
			}
		}
		ksort( $additionalPOs );
		foreach ( $additionalPOs as $key => $value ) {
			if ( is_array( $propertyValues ) && array_key_exists( $key, $propertyValues ) ) {
				/*
				 * Make an element for additional properties
				 */
				$result .= Html::openElement( 'div', array( 'id' => "sort_div_$i", 'class' => 'smwsort' ) );
				$result .= '<span class="smwquisortlabel"><span class="smw-remove"><a href="javascript:removePOInstance(\'sort_div_' . $i . '\')"><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>';
				$result .= wfMsg( 'smw_qui_property' ) . '</span>';
				$result .= Html::input( 'property[' . $i . ']', $propertyValues[$key], 'text', array( 'size' => '25', 'id' => "property$i" ) ) . "\n";
				$result .= Html::openElement( 'select', array( 'name' => "order[$i]" ) );

				$if1 = ( !is_array( $orderValues ) || !array_key_exists( $key, $orderValues ) || $orderValues[$key] == 'NONE' );
				$result .= Xml::option( wfMsg( 'smw_qui_nosort' ), "NONE", $if1 );

				$if2 = ( is_array( $orderValues ) && array_key_exists( $key, $orderValues ) && $orderValues[$key] == 'ASC' );
				$result .= Xml::option( wfMsg( 'smw_qui_ascorder' ), "ASC", $if2 );

				$if3 = ( is_array( $orderValues ) && array_key_exists( $key, $orderValues ) && $orderValues[$key] == 'DESC' );
				$result .= Xml::option( wfMsg( 'smw_qui_descorder' ), "DESC", $if3 );

				$result .= Xml::closeElement( 'select' );

				$if4 = ( is_array( $displayValues ) && array_key_exists( $key, $displayValues ) );
				$result .= Xml::checkLabel( wfMsg( 'smw_qui_shownresults' ), "display[$i]", "display$i", $if4 );

				if ( is_array( $propertyLabelValues ) && array_key_exists( $key, $propertyLabelValues ) ) {
					$result .= Html::hidden( "prop_label[$i]", $propertyLabelValues[$key], array( 'id' => "prop_label$i" ) );
				} else {
					$result .= Html::hidden( "prop_label[$i]", '', array( 'id' => "prop_label$i" ) );
				}
				if ( is_array( $propertyFormatValues ) && array_key_exists( $key, $propertyFormatValues ) ) {
					$result .= Html::hidden( "prop_format[$i]", $propertyFormatValues[$key], array( 'id' => "prop_format$i" ) );
				} else {
					$result .= Html::hidden( "prop_format[$i]", '', array( 'id' => "prop_format$i" ) );
				}
				if ( is_array( $propertyLimitValues ) && array_key_exists( $key, $propertyLimitValues ) ) {
					$result .= Html::hidden( "prop_limit[$i]", $propertyLimitValues[$key], array( 'id' => "prop_limit$i" ) );
				} else {
					$result .= Html::hidden( "prop_limit[$i]", '', array( 'id' => "prop_limit$i" ) );
				}
				$result .= ' <a  id="more' . $i . '" "class="smwq-more" href="javascript:smw_makePropDialog(\'' . $i . '\')"> ' . WfMsg( 'smw_qui_options' ) . ' </a> ';

				$result .= Xml::closeElement( 'div' );
				$i++;
			}
			if ( is_array( $categoryValues ) && array_key_exists( $key, $categoryValues ) &&
					is_array( $categoryLabelValues ) && array_key_exists( $key, $categoryLabelValues ) &&
					is_array( $categoryYesValues ) && array_key_exists( $key, $categoryYesValues ) &&
					is_array( $categoryNoValues ) && array_key_exists( $key, $categoryNoValues ) ) {
				/*
				 * Make an element for additional categories
				 */
				$result .= Html::openElement( 'div', array( 'id' => "sort_div_$i", 'class' => 'smwsort' ) );
				$result .= '<span class="smwquisortlabel"><span class="smw-remove"><a href="javascript:removePOInstance(\'sort_div_' . $i . '\')"><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>' .
							wfMsg( 'smw_qui_category' ) . '</span>' .
							Xml::input( "category[$i]", '25', $categoryValues[$key], array( 'id' => "category$i" ) ) . " " .
							Html::hidden( "cat_label[$i]", $categoryLabelValues[$key], array( 'id' => "cat_label$i" ) ) .
							Html::hidden( "cat_yes[$i]", $categoryYesValues[$key], array( 'id' => "cat_yes$i" ) ) .
							Html::hidden( "cat_no[$i]", $categoryNoValues[$key], array( 'id' => "cat_no$i" ) ) .
							' <a  id="more' . $i . '" "class="smwq-more" href="javascript:smw_makeCatDialog(\'' . $i . '\')"> ' . WfMsg( 'smw_qui_options' ) . ' </a> ' .
							Xml::closeElement( 'div' );
				$i++;
			}
			if ( is_array( $mainColumnLabels ) && array_key_exists( $key, $mainColumnLabels ) ) {
				/*
				 * Make an element for main column
				 */
				$result .= Html::openElement( 'div', array( 'id' => "sort_div_$i", 'class' => 'smwsort' ) ) .
					'<span class="smwquisortlabel"><span class="smw-remove"><a href="javascript:removePOInstance(\'sort_div_' . $i . '\')"><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>' .
					wfMsg( 'smw_qui_rescol' ) . '</span>' .
					Xml::input( "maincol_label[$i]", '20', $mainColumnLabels[$key], array ( 'id' => "maincol_label$i" ) ) . " " .
					Xml::closeElement( 'div' );
				$i++;
			}
		}
		$numSortValues = $i;
		// END: create form elements already submitted earlier via form

		// create hidden form elements to be cloned later
		$hiddenProperty = Html::openElement( 'div', array( 'id' => 'property_starter', 'class' => 'smwsort', 'style' => 'display:none' ) ) .
					'<span class="smwquisortlabel">' . '<span class="smw-remove"><a><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>' .
					wfMsg( 'smw_qui_property' ) . '</span>' .
					Xml::input( 'property_num', '25' ) . " " .
					Html::openElement( 'select', array( 'name' => 'order_num' ) ) .
						Xml::option( wfMsg( 'smw_qui_nosort' ), 'NONE' ) .
						Xml::option( wfMsg( 'smw_qui_ascorder' ), 'ASC' ) .
						Xml::option( wfMsg( 'smw_qui_descorder' ), 'DESC' ) .
					Xml::closeElement( 'select' ) .
					Html::hidden( 'prop_label_num', '' ) .
					Html::hidden( 'prop_format_num', '' ) .
					Html::hidden( 'prop_limit_num', '' ) .
					Xml::checkLabel( wfMsg( 'smw_qui_shownresults' ), "display_num", '', true ) .
					Xml::closeElement( 'div' );
		$hiddenProperty = json_encode( $hiddenProperty );

		$hiddenCategory = Html::openElement( 'div', array( 'id' => 'category_starter', 'class' => 'smwsort', 'style' => 'display:none' ) ) .
					'<span class="smwquisortlabel">' . '<span class="smw-remove"><a><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>' .
					wfMsg( 'smw_qui_category' ) . '</span>' .
					Xml::input( "category_num", '25' ) . " " .
					'<input type="hidden" name="cat_label_num" />' .
					'<input type="hidden" name="cat_yes_num" />' .
					'<input type="hidden" name="cat_no_num" />' .
					Xml::closeElement( 'div' );
		$hiddenCategory = json_encode( $hiddenCategory );

		$hiddenMainColumn = Html::openElement( 'div', array( 'id' => 'maincol_starter', 'class' => 'smwsort', 'style' => 'display:none' ) ) .
					'<span class="smwquisortlabel">' . '<span class="smw-remove"><a><img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '"></a></span>' .
					wfMsg( 'smw_qui_rescol' ) . '</span>' .
					Xml::input( "maincol_label_num", '25' ) . " " .
					Xml::closeElement( 'div' );
		$hiddenMainColumn = json_encode( $hiddenMainColumn );

		// create dialogbox for Property options
		$propertyHtml = Xml::inputLabelSep( 'Property:', '', 'd-property', 'd-property' ); // todo i18n
		$propertyLabelHtml = Xml::inputLabelSep( 'Label:', '', 'd-prop-label', 'd-prop-label' );// todo i18n
		$propertyFormatHtml = Xml::inputLabelSep( 'Format:', '', 'd-prop-format', 'd-prop-format' );// todo i18n
		$propertyLimitHtml = Xml::inputLabelSep( 'Limit:', 'd-prop-limit', 'd-prop-limit' ); // todo i18n
		$propertyDialogBox = Xml::openElement( 'div', array( 'id' => 'prop-dialog', 'title' => wfMsg( 'smw_prp_options' ), 'class' => 'smwpropdialog' ) ) .
					'<table>' .
						'<tr><td>' . $propertyHtml[0] . '</td><td>' . $propertyHtml[1] . '</td></tr>' .
						'<tr><td>' . $propertyLabelHtml[0] . '</td><td>' . $propertyLabelHtml[1] . '</td></tr>' .
						'<tr><td>' . $propertyLimitHtml[0] . '</td><td>' . $propertyLimitHtml[1] . '</td></tr>' .
						'<tr><td>' . $propertyFormatHtml[0] . '</td><td>' . $propertyFormatHtml[1] . '</td></tr>' .
					'</table>' .
					Xml::closeElement( 'div' );

		// create dialogbox for Category options
		$categoryHtml = Xml::inputLabelSep( wfMsg( 'smw_qui_dcategory' ), '', 'd-category', 'd-category' );
		$categoryLabelHtml = Xml::inputLabelSep( wfMsg( 'smw_qui_dlabel' ), '', 'd-category-label', 'd-category-label' );
		$categoryYesHtml = Xml::inputLabelSep( wfMsg( 'smw_qui_dcatyes' ), '', 'd-category-yes', 'd-category-yes' );
		$categoryNoHtml = Xml::inputLabelSep( wfMsg( 'smw_qui_dcatno' ), '', 'd-category-no', 'd-category-no' );
		$categoryDialogBox = Xml::openElement( 'div', array( 'id' => 'cat-dialog', 'title' => wfMsg( 'smw_qui_catopts' ), 'class' => 'smwcatdialog' ) ) .
					'<table>' .
						'<tr><td>' . $categoryHtml[0] . '</td><td>' . $categoryHtml[1] . '</td></tr>' .
						'<tr><td>' . $categoryLabelHtml[0] . '</td><td>' . $categoryLabelHtml[1] . '</td></tr>' .
						'<tr><td>' . $categoryYesHtml[0] . '</td><td>' . $categoryYesHtml[1] . '</td></tr>' .
						'<tr><td>' . $categoryNoHtml[0] . '</td><td>' . $categoryNoHtml[1] . '</td></tr>' .
					'</table>' .
					Xml::closeElement( 'div' );

		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '[<a href="javascript:smw_addPropertyInstance(\'property_starter\', \'sorting_main\')">' . wfMsg( 'smw_qui_addnprop' ) . '</a>]' .
					'[<a href="javascript:smw_addCategoryInstance(\'category_starter\', \'sorting_main\')">' . wfMsg( 'smw_qui_addcategory' ) . '</a>]' .
					'[<a href="javascript:smw_addMainColInstance(\'maincol_starter\', \'sorting_main\')">' . wfMsg( 'smw_qui_addrescol' ) . '</a>]' .
					"\n";

		// Javascript code for handling adding and removing the "sort" inputs
		if ( $enableAutocomplete == SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
			$this->addAutocompletionJavascriptAndCSS();
		}
		// localisation messages for javascript
		$optionsMsg = wfMsg( 'smw_qui_options' );
		$okMsg = wfMsg( 'smw_qui_ok' );
		$cancelMsg = wfMsg( 'smw_qui_cancel' );
		$javascriptText = <<<EOT
<script type="text/javascript">
var num_elements = {$numSortValues};
EOT;
// add autocomplete
		if ( $enableAutocomplete == SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
			$javascriptText .= <<<EOT

function smw_property_autocomplete(){
	jQuery('[name*="property"]').autocomplete({
		minLength: 2,
		source: function(request, response) {
			url=wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['property']+'&format=jsonfm';

			jQuery.getJSON(url, 'search='+request.term, function(data){
				//remove the namespace prefix 'Property:' from returned data
				for(i=0;i<data[1].length;i++) data[1][i]=data[1][i].substr(data[1][i].indexOf(':')+1);
				response(data[1]);
			});

		}
	});
}

function smw_category_autocomplete(){
		jQuery('[name*="category"]').autocomplete({
		minLength: 2,
		source: function(request, response) {
			url=wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['category']+'&format=jsonfm';

			jQuery.getJSON(url, 'search='+request.term, function(data){
				//remove the namespace prefix 'Category:' from returned data
				for(i=0;i<data[1].length;i++) data[1][i]=data[1][i].substr(data[1][i].indexOf(':')+1);
				response(data[1]);
			});

		}
	});
}
EOT;
		} else {
			$javascriptText .= <<<EOT
function smw_property_autocomplete(){
}

function smw_category_autocomplete(){
}

EOT;
		}

		$javascriptText .= <<<EOT

function smw_makeCatDialog(cat_id){
	jQuery('#prop-cat input').attr('value','');

	cat=jQuery('#category'+cat_id)[0].value;
	label=jQuery('#cat_label'+cat_id)[0].value;
	yes = jQuery('#cat_yes'+cat_id)[0].value;
	no = jQuery('#cat_no'+cat_id)[0].value;

	jQuery('#d-category-yes').attr('value',yes);
	jQuery('#d-category-no').attr('value',no);
	jQuery('#d-category-label').attr('value',label);
	jQuery('#d-category').attr('value',cat);

	jQuery('#cat-dialog').dialog.id=cat_id;
	jQuery('#cat-dialog').dialog('open');
}

function smw_makePropDialog(prop_id){
	jQuery('#prop-dialog input').attr('value','');
	prop=jQuery('#property'+prop_id).attr('value');
	label=jQuery('#prop_label'+prop_id).attr('value');
	format=jQuery('#prop_format'+prop_id).attr('value');
	limit=jQuery('#prop_limit'+prop_id).attr('value');
	jQuery('#d-property').attr('value', prop);
	jQuery('#d-prop-label').attr('value', label);
	jQuery('#d-prop-limit').attr('value', limit);
	jQuery('#d-prop-format').attr('value', format);
	jQuery('#prop-dialog').dialog.id=prop_id;
	jQuery('#prop-dialog').dialog('open');
}
// code for handling adding and removing the "sort" inputs

function smw_addPropertyInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.id = div_id;
	new_div.style.display = 'block';
	jQuery(new_div.getElementsByTagName('label')).attr('for', 'display'+num_elements);
	var children = new_div.getElementsByTagName('*');
	var x;
	for (x = 0; x < children.length; x++) {
		if (jQuery(children[x]).attr('for')) jQuery(children[x]).attr('for',"display"+num_elements);
		if (children[x].name){
			children[x].id = children[x].name.replace(/_num/, ''+num_elements);
			children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
		}
	}

	//Create 'more' link
	var more_button =document.createElement('span');
	more_button.innerHTML = ' <a class="smwq-more" href="javascript:smw_makePropDialog(\'' + num_elements + '\')">{$optionsMsg}</a> ';
	more_button.id = 'more'+num_elements;
	new_div.appendChild(more_button);

	//Add the new instance
	main_div.appendChild(new_div);

	// initialize delete button
	st='sort_div_'+num_elements;
	jQuery('#'+new_div.id).find(".smw-remove a")[0].href="javascript:removePOInstance('"+st+"')";
	num_elements++;
	smw_property_autocomplete();
}

function smw_addCategoryInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.id = div_id;
	new_div.style.display = 'block';
	jQuery(new_div.getElementsByTagName('label')).attr('for', 'display'+num_elements);
	var children = new_div.getElementsByTagName('*');
	var x;
	for (x = 0; x < children.length; x++) {
		if (jQuery(children[x]).attr('for')) jQuery(children[x]).attr('for',"display"+num_elements);
		if (children[x].name){
			children[x].id = children[x].name.replace(/_num/, ''+num_elements);
			children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
		}
	}

	//Create 'more' link
	var more_button =document.createElement('span');
	more_button.innerHTML = ' <a class="smwq-more" href="javascript:smw_makeCatDialog(\'' + num_elements + '\')">{$optionsMsg}</a> ';
	more_button.id = 'more'+num_elements;
	new_div.appendChild(more_button);

	//Add the new instance
	main_div.appendChild(new_div);

	// initialize delete button
	st='sort_div_'+num_elements;
	jQuery('#'+new_div.id).find(".smw-remove a")[0].href="javascript:removePOInstance('"+st+"')";
	num_elements++;
	smw_category_autocomplete();
}

function smw_addMainColInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.id = div_id;
	new_div.style.display = 'block';
	jQuery(new_div.getElementsByTagName('label')).attr('for', 'display'+num_elements);
	var children = new_div.getElementsByTagName('*');
	var x;
	for (x = 0; x < children.length; x++) {
		if (jQuery(children[x]).attr('for')) jQuery(children[x]).attr('for',"display"+num_elements);
		if (children[x].name){
			children[x].id = children[x].name.replace(/_num/, ''+num_elements);
			children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
		}
	}

	//Add the new instance
	main_div.appendChild(new_div);

	// initialize delete button
	st='sort_div_'+num_elements;
	jQuery('#'+new_div.id).find(".smw-remove a")[0].href="javascript:removePOInstance('"+st+"')";
	num_elements++;
	smw_category_autocomplete();
}

function removePOInstance(div_id) {
	var olddiv = document.getElementById(div_id);
	var parent = olddiv.parentNode;
	parent.removeChild(olddiv);
}

jQuery(function(){
	jQuery('$hiddenProperty').appendTo(document.body);
	jQuery('$hiddenCategory').appendTo(document.body);
	jQuery('$hiddenMainColumn').appendTo(document.body);
	jQuery('$propertyDialogBox').appendTo(document.body);
	jQuery('$categoryDialogBox').appendTo(document.body);
	jQuery('#cat-dialog').dialog({
		autoOpen: false,
		modal: true,
		resizable: true,
		minHeight: 200,
		minWidth: 400,
		buttons: {
			"{$okMsg}": function(){
				cat = jQuery('#d-category').attr('value');
				label = jQuery('#d-category-label').attr('value');
				yes = jQuery('#d-category-yes').attr('value');
				no = jQuery('#d-category-no').attr('value');
				id=jQuery(this).dialog.id;

				jQuery('#category'+id).attr('value',cat);
				jQuery('#cat_label'+id).attr('value',label);
				jQuery('#cat_yes'+id).attr('value',yes);
				jQuery('#cat_no'+id).attr('value',no);
				jQuery(this).dialog("close");
			},
			"{$cancelMsg}": function(){
				jQuery('#cat-dialog input').attr('value','');
				jQuery(this).dialog("close");
			}
		}
	});

	jQuery('#prop-dialog').dialog({
		autoOpen: false,
		modal: true,
		resizable: true,
		buttons: {
			"{$okMsg}": function(){
				id=jQuery(this).dialog.id;
				property=jQuery('#d-property').attr('value');
				label=jQuery('#d-prop-label').attr('value');
				limit=jQuery('#d-prop-limit').attr('value');
				format=jQuery('#d-prop-format').attr('value');


				jQuery('#property'+id).attr('value',property);
				jQuery('#prop_label'+id).attr('value',label);
				jQuery('#prop_limit'+id).attr('value',limit);
				jQuery('#prop_format'+id).attr('value',format);
				jQuery(this).dialog("close");
			},
			"{$cancelMsg}": function(){
				jQuery('#prop-dialog input').attr('value','');
				jQuery(this).dialog("close");
			}
		}
	});
	jQuery('#sort-more').click(function(){jQuery('#prop-dialog').dialog("open");});
	jQuery('#d-category').bind('change keyup focus click',function(){
		if(jQuery(this).attr('value')==''){
			jQuery('#d-category-yes').css('visibility','hidden');
			jQuery('#d-category-no').css('visibility','hidden');
			jQuery('#cat-dialog [for="d-category-no"]').css('visibility','hidden');
			jQuery('#cat-dialog [for="d-category-yes"]').css('visibility','hidden');
		} else {
			jQuery('#d-category-yes').css('visibility','visible');
			jQuery('#d-category-no').css('visibility','visible');
			jQuery('#cat-dialog [for="d-category-no"]').css('visibility','visible');
			jQuery('#cat-dialog [for="d-category-yes"]').css('visibility','visible');
		}
	});
});

jQuery(document).ready(smw_property_autocomplete);
jQuery(document).ready(smw_category_autocomplete);
</script>

EOT;

		$wgOut->addScript( $javascriptText );
		return $result;
	}

	/**
	 * Generates the forms elements(s) for adding sorting options. Use its
	 * complement processSortingFormBox() to decode sorting data sent
	 * by these elements.
	 *
	 * @return string
	 */
	protected function getSortingFormBox() {
		global $smwgQSortingSupport, $wgRequest, $wgOut;

		if ( !$smwgQSortingSupport ) return '';
		$params = $this->uiCore->getParameters();

		$result = '';
		if ( array_key_exists( 'sort', $params ) && array_key_exists( 'order', $params ) ) {
			$sorts = explode( ',', $params['sort'] );
			$orders = explode( ',', $params['order'] );
			reset( $sorts );
		} else {
			$orders = array(); // do not even show one sort input here
		}

		foreach ( $orders as $i => $order ) {
			$result .=  "<div id=\"sort_div_$i\">" . wfMsg( 'smw_ask_sortby' ) . ' <input type="text" name="sort[' . $i . ']" value="' .
					htmlspecialchars( $sorts[$i] ) . "\" size=\"25\"/>\n" . '<select name="order[' . $i . ']"><option ';
				if ( $order == 'ASC' ) $result .= 'selected="selected" ';
			$result .=  'value="ASC">' . wfMsg( 'smw_qui_ascorder' ) . '</option><option ';
				if ( $order == 'DESC' ) $result .= 'selected="selected" ';

			$result .=  'value="DESC">' . wfMsg( 'smw_qui_descorder' ) . "</option></select>\n";
			$result .= '[<a class="smwq-remove" href="javascript:removeInstance(\'sort_div_' . $i . '\')">' . wfMsg( 'smw_qui_delete' ) . '</a>]' . "\n";
			$result .= "</div>\n";
		}

		$hidden .=  '<div id="sorting_starter" style="display: none">' . wfMsg( 'smw_ask_sortby' ) . ' <input type="text" size="25" />' . "\n";
		$hidden .= ' <select name="order_num">' . "\n";
		$hidden .= '	<option value="ASC">' . wfMsg( 'smw_qui_ascorder' ) . "</option>\n";
		$hidden .= '	<option value="DESC">' . wfMsg( 'smw_qui_descorder' ) . "</option>\n</select>\n";
		$hidden .= "</div>\n";
		$hidden = json_encode( $hidden );

		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '<a href="javascript:addInstance(\'sorting_starter\', \'sorting_main\')">' . wfMsg( 'smw_add_sortcondition' ) . '</a>' . "\n";

		$num_sort_values = 0;

		if  ( !array_key_exists( 'sort', $params ) ) {
			$sort_values = $wgRequest->getArray( 'sort' );
			if ( is_array( $sort_values ) ) {
				$params['sort'] = implode( ',', $sort_values );
				$num_sort_values = count( $sort_values );
			}
		}
		// Javascript code for handling adding and removing the "sort" inputs
		$delete_msg = wfMsg( 'smw_qui_delete' );

		$this->enableJQuery();
		$javascriptText = <<<EOT
<script type="text/javascript">
// code for handling adding and removing the "sort" inputs
jQuery(document).ready(function(){
		jQuery('$hidden').appendTo(document.body);
	});
var num_elements = {$num_sort_values};

function addInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.className = 'multipleTemplate';
	new_div.id = div_id;
	new_div.style.display = 'block';

	var children = new_div.getElementsByTagName('*');
	var x;
	for (x = 0; x < children.length; x++) {
		if (children[x].name)
			children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
	}

	//Create 'delete' link
	var remove_button = document.createElement('span');
	remove_button.innerHTML = '[<a href="javascript:removeInstance(\'sort_div_' + num_elements + '\')">{$delete_msg}</a>]';
	new_div.appendChild(remove_button);

	//Add the new instance
	main_div.appendChild(new_div);
	num_elements++;
}

function removeInstance(div_id) {
	var olddiv = document.getElementById(div_id);
	var parent = olddiv.parentNode;
	parent.removeChild(olddiv);
}
</script>

EOT;

		$wgOut->addScript( $javascriptText );
		return $result;
	}

	/**
	 * Decodes form Sorting options sent through form-elements generated by
	 * its complement, getSortingFormBox(). UIs may overload both to change form
	 * parameters.
	 *
	 * @global boolean $smwgQSortingSupport
	 * @param WebRequest $wgRequest
	 * @return string
	 * @todo build in validation for sorting
	 */
	protected function processSortingFormBox( WebRequest $wgRequest ) {
		global $smwgQSortingSupport;
		if ( !$smwgQSortingSupport ) return array();

		$params = array();
		$orderValues = $wgRequest->getArray( 'order' );
		if ( is_array( $orderValues ) ) {
			$params['order'] = '';
			foreach ( $orderValues as $order_value ) {
				if ( $order_value == '' ) {
					$order_value = 'ASC';
				}
				$params['order'] .= ( $params['order'] != '' ? ',' : '' ) . $order_value;
			}
		}

		$sort_values = $wgRequest->getArray( 'sort' );
		if ( is_array( $sort_values ) ) {
			$params['sort'] = '';
			foreach ( $sort_values as $sort_value ) {
				$params['sort'] .= ( $params['sort'] != '' ? ',' : '' ) . $sort_value;
			}
		}
		return $params;

	}

	/**
	 * Generates the form element(s) for PrintOuts.
	 * Use its complement processPOFormBox() to decode data sent through these
	 * form elements. UIs may overload both to change the form parameter or the
	 * html elements.
	 *
	 * @param boolean $enableAutocomplete If set to true, adds the relevant JS and CSS to the page
	 * @return string The HTML code
	 */
	protected function getPOFormBox( $enableAutocomplete = SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
		global $wgOut;

		if ( $enableAutocomplete ) {
			$this->addAutocompletionJavascriptAndCSS();
			$javascriptAutocompleteText = <<<EOT
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery("#add_property").autocomplete({
		minLength: 2,
		source: function(request, response) {
			request.term=request.term.substr(request.term.lastIndexOf("\\n")+1);
			url=wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['property']+'&format=jsonfm';

			jQuery.getJSON(url, 'search='+request.term, function(data){
				//remove the namespace prefix 'Property:' from returned data and add prefix '?'
				for(i=0;i<data[1].length;i++) data[1][i]="?"+data[1][i].substr(data[1][i].indexOf(':')+1);
				response(jQuery.ui.autocomplete.filter(data[1], smw_escapeQuestion(smw_extractLast(request.term))));
			});
		},
		focus: function() {
			// prevent value inserted on focus
			return false;
		},
		select: function(event, ui) {
			var terms = smw_split( this.value );
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push( ui.item.value );
			// add placeholder to get the comma-and-space at the end
			terms.push("");
			this.value = terms.join("\\n");
			return false;
		}
	});
});
</script>
EOT;

			$wgOut->addScript( $javascriptAutocompleteText );

		}

		return Html::element( 'textarea', array( 'id' => 'add_property', 'name' => 'po', 'cols' => '20', 'rows' => '6' ), $this->getPOStrings() );
	}

	/**
	 * Decodes form data sent through form-elements generated by
	 * its complement, getPOFormBox(). UIs may overload both to change form
	 * parameters.
	 *
	 * @param WebRequest $wgRequest
	 * @return array
	 */
	protected function processPOFormBox( WebRequest $wgRequest ) {
		$postring = $wgRequest->getText( 'po' );
		$poArray = array();

		if ( $postring != '' ) { // parameters from HTML input fields
			$ps = explode( "\n", $postring ); // params separated by newlines here (compatible with text-input for printouts)

			foreach ( $ps as $param ) { // add initial ? if omitted (all params considered as printouts)
				$param = trim( $param );

				if ( ( $param != '' ) && ( $param[0] != '?' ) ) {
					$param = '?' . $param;
				}

				$poArray[] = $param;
			}
		}

		return $poArray;
	}

	/**
	 * Generates the url parameters based on passed parameters.
	 * UI implementations need to overload this if they use different form
	 * parameters.
	 *
	 * @return string An url-encoded string.
	 */
	protected function getUrlTail() {
		$urlTail = '&q=' . urlencode( $this->uiCore->getQuerystring() );

		$tmpArray = array();
		$params = $this->uiCore->getParameters();
		foreach ( $params as $key => $value ) {
			if ( !in_array( $key, array( 'sort', 'order', 'limit', 'offset', 'title' ) ) ) {
				$tmpArray[$key] = $value;
			}
		}
		$urlTail .= '&p=' . urlencode( SMWInfolink::encodeParameters( $tmpArray ) );

		$printOutString = '';
		foreach ( $this->uiCore->getPrintOuts() as $printout ) {
			$printOutString .= $printout->getSerialisation() . "\n";
		}

		if ( $printOutString != '' ) {
			$urlTail .= '&po=' . urlencode( $printOutString );
		}
		if ( array_key_exists( 'sort', $params ) ) {
			$urlTail .= '&sort=' . $params['sort'];
		}
		if ( array_key_exists( 'order', $params ) ) {
			$urlTail .= '&order=' . $params['order'];
		}

		return $urlTail;
	}

	/**
	 * Displays a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer.
	 *
	 * @param string $format
	 * @param array $paramValues The current values for the parameters (name => value)
	 * @param array $ignoredAttribs Attributes which should not be generated by this method.
	 *
	 * @return string
	 */
	protected function showFormatOptions( $format, array $paramValues, array $ignoredAttribs = array() ) {
		$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );

		$params = method_exists( $printer, 'getValidatorParameters' ) ? $printer->getValidatorParameters() : array();

		$optionsHtml = array();

		foreach ( $params as $param ) {
			// Ignore the parameters for which we have a special control in the GUI already.
			if ( in_array( $param->getName(), $ignoredAttribs ) ) {
				continue;
			}

			$currentValue = array_key_exists( $param->getName(), $paramValues ) ? $paramValues[$param->getName()] : false;

			$optionsHtml[] =
				Html::rawElement(
					'div',
					array(
						'style' => 'width: 30%; padding: 5px; float: left;'
					),
					'<label for="p[' . htmlspecialchars( $param->getName() ) . ']">' . htmlspecialchars( $param->getName() ) . ': </label>' .
					$this->showFormatOption( $param, $currentValue ) .
					'<br />' .
					Html::element( 'em', array(), $param->getDescription() )
				);
		}

		for ( $i = 0, $n = count( $optionsHtml ); $i < $n; $i++ ) {
			if ( $i % 3 == 2 || $i == $n - 1 ) {
				$optionsHtml[$i] .= "<div style=\"clear: both\";></div>\n";
			}
		}

		$i = 0;
		$rowHtml = '';
		$resultHtml = '';
		$flipStyle = true;
		$flipCount = 0;
		while ( $option = array_shift( $optionsHtml ) ) {
			$rowHtml .= $option;
			$i++;

			$resultHtml .= Html::rawElement(
				'div',
				array(
					'style' => 'background: ' . ( $flipStyle ? 'white' : '#dddddd' ) . ';'
				),
				$rowHtml
			);

			$rowHtml = '';
			$flipCount++;
			if ( $flipCount == 3 ) {
				$flipStyle = !$flipStyle;
				$flipCount = 0;
			}

		}

		return $resultHtml;
	}


	/**
	 * Get the HTML for a single parameter input.
	 *
	 * @param Parameter $parameter
	 * @param mixed $currentValue
	 *
	 * @return string
	 */
	protected function showFormatOption( Parameter $parameter, $currentValue ) {
		$input = new ParameterInput( $parameter );
		$input->setInputName( 'p[' . $parameter->getName() . ']' );

		if ( $currentValue !== false ) {
			$input->setCurrentValue( $currentValue );
		}

		return $input->getHtml();
	}

	/**
	 * Creates form elements for choosing the result-format and their
	 * associated format.
	 *
	 * The drop-down list and the format options are returned seperately as
	 * elements of an array.Use in conjunction with processFormatOptions() to
	 * supply formats options using ajax. Also, use its complement
	 * processFormatSelectBox() to decode form data sent by these elements.
	 * UI's may overload these methods to change behaviour or form
	 * parameters.
	 *
	 * @param string $defaultFormat The default format which remains selected in the form
	 * @return array The first element contains the format selector, while the second contains the Format options
	 */
	protected function getFormatSelectBoxSep( $defaultFormat = 'broadtable' ) {
		global $smwgResultFormats, $wgOut;

		$this->enableJQuery();

		// checking argument
		$defFormat = 'broadtable';
		if ( array_key_exists( $defaultFormat, $smwgResultFormats ) ) {
			$defFormat = $defaultFormat;
		}

		$printer = SMWQueryProcessor::getResultPrinter( $defFormat, SMWQueryProcessor::SPECIAL_PAGE );
		$url = $this->getTitle()->getLocalURL( "showformatoptions=' + this.value + '" );

		foreach ( $this->uiCore->getParameters() as $param => $value ) {
			if ( $param !== 'format' ) {
				$url .= '&params[' . Xml::escapeJsString( $param ) . ']=' . Xml::escapeJsString( $value );
			}
		}

		$result[0] = "\n" .	'<select id="formatSelector" name="p[format]" onChange="JavaScript:updateOtherOptions(\'' . $url . '\')">' . "\n" .
			'<option value="' . $defFormat . '">' . $printer->getName() .
			' (' . wfMsg( 'smw_ask_defaultformat' ) . ')</option>' . "\n";

		$formats = array();
		foreach ( array_keys( $smwgResultFormats ) as $format ) {
			// Special formats "count" and "debug" currently not supported.
			if ( $format != $defFormat && $format != 'count' && $format != 'debug' ) {
				$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );
				$formats[$format] = $printer->getName();
			}
		}
		natcasesort( $formats );

		$params = $this->uiCore->getParameters();
		foreach ( $formats as $format => $name ) {
			$result[0] .= '<option value="' . $format . '"' . ( $params['format'] == $format ? ' selected' : '' ) . '>' . $name . "</option>\n";
		}

		$result[0] .= "</select>";
		$result[0] .= "\n";
		$result[] .= '<fieldset><legend>' . wfMsg( 'smw_ask_otheroptions' ) . "</legend>\n";
		$result[1] .= "<div id=\"other_options\">" . $this->showFormatOptions( $params['format'], $params ) . " </div>";
		$result[1] .= "</fieldset>\n";

		// BEGIN: add javascript for updating formating options by ajax
		$javascript = <<<END
<script type="text/javascript">
function updateOtherOptions(strURL) {
	jQuery.ajax({ url: strURL, context: document.body, success: function(data){
		jQuery("#other_options").html(data);
	}});
}
</script>
END;

		$wgOut->addScript( $javascript );
		// END: add javascript for updating formating options by ajax

		return $result;
	}

	/**
	 * A method which decodes form data sent through form-elements generated
	 * by its complement, getFormatSelectBox(). UIs may overload both to
	 * change form parameters.
	 *
	 * @param WebRequest $wgRequest
	 * @return array
	 */
	protected function processFormatSelectBox( WebRequest $wgRequest ) {
		$queryVal = $wgRequest->getVal( 'p' );

		if ( !empty( $queryVal ) ) {
			$params = SMWInfolink::decodeParameters( $queryVal, false );
		} else {
			$queryValues = $wgRequest->getArray( 'p' );

			if ( is_array( $queryValues ) ) {
				foreach ( $queryValues as $key => $val ) {
					if ( empty( $val ) ) unset( $queryValues[$key] );
				}
			}

			// p is used for any additional parameters in certain links.
			$params = SMWInfolink::decodeParameters( $queryValues, false );
		}

		return $params;
	}

	/**
	 * Generates form elements for a (web)requested format.
	 *
	 * Required by getFormatSelectBox() to recieve form elements from the Web.
	 * UIs may need to overload processFormatOptions(),
	 * processFormatSelectBox() and getFormatSelectBox() to change behavior.
	 *
	 * @param WebRequest $wgRequest
	 * @return boolean true if format options were requested and returned, else false
	 */
	protected function processFormatOptions( $wgRequest ) {
		global $wgOut;
		if ( $wgRequest->getCheck( 'showformatoptions' ) ) {
			// handle Ajax action
			$format = $wgRequest->getVal( 'showformatoptions' );
			$params = $wgRequest->getArray( 'params' );
			$wgOut->disable();
			echo $this->showFormatOptions( $format, $params );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the additional printouts as a string.
	 *
	 * UIs may overload this to change how this string should be displayed.
	 *
	 * @return string
	 */
	public function getPOStrings() {
		$string = '';
		$printOuts = $this->uiCore->getPrintOuts();
		if ( !empty( $printOuts ) ) {
			foreach ( $printOuts as $value ) {
				$string .= $value->getSerialisation() . "\n";
			}
		}
		return $string;
	}

	/**
	 * Returns true if this page shows the navigationBar. Overload to change
	 * behavior.
	 *
	 * @return boolean
	 */
	protected function usesNavigationBar() {
		// hide if no results are found
		return ( $this->uiCore->getResultCount() != 0 );
	}

}

/**
 * This class captures the core activities of what a semantic search page should
 * do: take parameters, validate them and generate results, or errors, if any.
 *
 * Query UIs may use this class to create a customised UI interface. In most
 * cases, one is likely to extend the SMWQueryUI class to build a Search Special
 * page. However in order to acces some core featues, one may directly access
 * the methods of this class.
 *
 * This class does not define the format in which data should be passed through
 * the web, except those already defined by SMWInfolink.
 *
 * @author Devayon Das
 *
 */
class SMWQueryUIHelper {

	/**
	 * The query passed by the user.
	 * @var string
	 */
	protected $queryString = '';

	/**
	 * Various parameters passed by the user which control the format,
	 * limit, offset.
	 * @var array of strings
	 */
	protected $parameters = array();

	/**
	 * The additional columns to be displayed with results.
	 * @var array of SMWPrintRequest
	 */
	protected $printOuts = array(); // Properties to be printed along with results

	/**
	 * The The additional columns to be displayed with results in
	 * '?property' form.
	 *
	 * @var array of strings
	 */
	protected $printOutStrings = array();

	/**
	 * Have errors occured so far?
	 * @var boolean
	 */
	private $errorsOccured = false;

	/**
	 * Has the query come from a special page, or from an InfoLink?
	 *
	 * @var mixed SMWQueryUIHelper::SPECIAL_PAGE | SMWQueryUIHelper::WIKI_LINK
	 */
	private $context;

	/**
	 * Error messages if any
	 *
	 * @var array of string
	 */
	private $errors = array();

	/**
	 * The default result printer if no format is set at the higher level
	 */
	private $defaultResultPrinter = 'broadtable';

	/**
	 * The Query Result, if it has been fetched.
	 *
	 * @var SMWQueryResult
	 */
	private $queryResult = null;

	/*
	 * Constants define whether the parameters was passed from the ui form (SPECIAL_PAGE)
	 * or from the further results infolink (WIKI_LINK)
	 */
	const SPECIAL_PAGE = 0;// parameters passed from special page
	const WIKI_LINK = 1;// parameters passed from 'further links' in the wiki.

	/**
	 * A list of Query UIs
	 *
	 * @var array of SpecialPage
	 */
	protected static $uiPages = array();

	/**
	 * Although this constructor is publicly accessible, its use is discouraged.
	 * Instantiation can instead be done by the makeForInfoLink() to handle infolink
	 * requests or makeForUI() to handle requests from a Query form.
	 *
	 * @param mixed $context SMWQueryUIHelper::SPECIAL_PAGE | SMWQueryUIHelper::WIKI_LINK
	 */
	public function __construct( $context = SMWQueryUIHelper::SPECIAL_PAGE ) {
		$this->context = $context;
	}

	/**
	 * Returns true if any errors have occured
	 *
	 * @return boolean
	 */
	public function hasError() {
		return $this->errorsOccured;
	}

	/**
	 * Returns the limit of results defined. If not set, it returns 0.
	 *
	 * @return integer
	 */
	public function getLimit() {
		if ( array_key_exists( 'limit', $this->parameters ) ) {
			return $this->parameters['limit'];
		} else {
			return 0;
		}
	}

	/**
	 * Returns the offset of results. If it isnt defined, returns a default
	 * value of 20.
	 *
	 * @return integer
	 */
	public function getOffset() {
		if ( array_key_exists( 'offset', $this->parameters ) ) {
			return $this->parameters['offset'];
		} else {
			return 20;
		}
	}

	/**
	 * Would there be more query results that were not shown due to a limit?
	 *
	 * @return boolean
	 */
	public function hasFurtherResults() {
		if ( !is_null( $this->queryResult ) ) { // The queryResult may not be set
			return $this->queryResult->hasFurtherResults();
		} else {
			return false;
		}
	}

	/**
	 * Returns a handle to the underlying Result object.
	 *
	 * @return SMWQueryResult
	 */
	public function getResultObject() {
		return $this->queryResult;
	}

	/**
	 * Returns an array of errors, if any have occured.
	 *
	 * @return array of strings
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Register a Semantic Search Special Page.
	 *
	 * This method can be used by any new Query UI to register itself.
	 * The corresponding method getUiList() would return the names of all
	 * lists Query UIs.
	 *
	 * @see getUiList()
	 * @param SpecialPage $page
	 */
	public static function addUI( SpecialPage &$page ) {
		/*
		* This way of registering, instead of using a global variable will cause
		* SMWQueryUIHelper to AutoLoad, but the alternate would break encapsulation.
		*/
		self::$uiPages[] = $page;
	}

	/**
	 * Returns an array of Semantic Search Special Pages
	 *
	 * @see addUI()
	 * @return array of SpecialPage
	 */
	public static function getUiList() {
		return self::$uiPages;
	}

	/**
	 * Sets up a query. If validation is enabled, then the query string is
	 * checked for errors.
	 *
	 * @param string $queryString The query
	 * @return array array of errors, if any.
	 */
	public function setQueryString( $queryString = "", $enableValidation = false ) {
		$this -> queryString = $queryString;

		$errors = array();
		if ( $enableValidation ) {
			if ( $queryString == '' ) {
				$errors[] = wfMsg( 'smw_qui_noquery' );
			} else {
				$query = SMWQueryProcessor::createQuery( $queryString, array() );
				$errors = $query ->getErrors();
			}
			if ( !empty ( $errors ) ) {
				$this->errorsOccured = true;
			}
			$this->errors = array_merge( $errors, $this->errors );
		}

		return $errors;
	}

	/**
	 *
	 * Sets up any extra properties which need to be displayed with results.
	 * Each string in printouts should be of the form "?property" or
	 * "property".
	 *
	 * When validation is enabled, the values in $printOuts are checked
	 * against properties which exist in the wiki, and a warning string (for
	 * each property) is returned. Returns an empty array otherwise.
	 *
	 * @param array $printOuts Array of strings
	 * @param boolean $enableValidation
	 * @return array Array of errors messages (strings), if any.
	 */
	public function setPrintOuts( array $printOuts = array(), $enableValidation = false ) {
		/*
		 * Note: property validation is not very clearly defined yet, so validation is disabled by default
		 */

		$errors = array();
		if ( $enableValidation ) {
			foreach ( $printOuts as $key => $prop ) {
				if ( $prop[0] != '?' ) {
					$printOuts[$key] = "?" . $printOuts[$key];
				}
				if ( !$this->validateProperty( $prop ) ) {
					$errors[] = wfMsg( 'smw_qui_invalidprop', $prop );
					$this->errorsOccured = true;
				}
			}
		}
		$this -> printOutStrings = $printOuts;
		$this->errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	/**
	 * Sets the parameters for the query.
	 *
	 * The structure of $params is defined partly by #ask and also by the
	 * Result Printer used. When validation is enabled, $params are checked
	 * for conformance, and error messages, if any, are returned.
	 *
	 * Although it is not mandatory for any params to be set while calling
	 * this method, this method must be called so that default parameters
	 * are used.
	 *
	 * @global int $smwgQMaxInlineLimit
	 * @global array $smwgResultFormats
	 * @param array $params
	 * @param boolean $enableValidation
	 * @return array of strings
	 */
	public function setParams( array $params = array(), $enableValidation = false ) {
		global $smwgQMaxInlineLimit, $smwgResultFormats;
		$errors = array();

		// checking for missing parameters and adding them
		if ( !array_key_exists( 'format', $params ) || ! array_key_exists ( $params['format'], $smwgResultFormats ) ) {
			$params[ 'format' ] = $this->defaultResultPrinter;
		}
		if ( !array_key_exists( 'limit', $params ) ) {
			$params[ 'limit' ] = 20;
		}
		$params[ 'limit' ] = min( $params[ 'limit' ], $smwgQMaxInlineLimit );
		if ( !array_key_exists( 'offset', $params ) ) {
			$params['offset'] = 0;
		}

		if ( $enableValidation ) { // validating the format
			if ( !array_key_exists( $params['format'], $smwgResultFormats ) ) {
				$errors[] = wfMsg( 'smw_qui_invalidformat', $params['format'] );
				$this->errorsOccured = true;
			} else { // validating parameters for result printer
				$printer = SMWQueryProcessor::getResultPrinter( $params[ 'format' ] );
				$para_meters = $printer->getParameters();
				if ( is_array( $para_meters ) ) {
					$validator = new Validator();
					$validator->setParameters( $params, $para_meters );
					$validator->validateParameters();
					if ( $validator->hasFatalError() ) {
						array_merge ( $errors, $validator->getErrorMessages () );
						$this->errorsOccured = true;
					}
				}
			}
		}

		$this->parameters = $params;
		$this->errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	/**
	 * Processes the QueryString, Params, and PrintOuts.
	 *
	 * @todo Combine this method with execute() or remove it altogether.
	 * @todo for wikilink context, try to avoid computation if no query is set,
	 * also check for pagination problems, if any.
	 */
	public function extractParameters( $p ) {
		if ( $this->context == self::SPECIAL_PAGE ) {
			// assume setParams(), setPintouts and setQueryString have been called
			$rawParams = array_merge( $this->parameters, array( $this->queryString ), $this->printOutStrings );
		} else {// context is WIKI_LINK
			$rawParams = SMWInfolink::decodeParameters( $p, true );
			// calling setParams to fill in missing parameters
			$this->setParams( $rawParams );
			$rawParams = $this->parameters;
		}

		SMWQueryProcessor::processFunctionParams( $rawParams, $this->queryString, $this->parameters, $this->printOuts );
	}

	/**
	 * Executes the query.
	 *
	 * This method can be called once $queryString, $parameters, $printOuts
	 * are set either by using the setQueryString(), setParams() and
	 * setPrintOuts() followed by extractParameters(), or one of the static
	 * factory methods such as makeForInfoLink() or makeForUI().
	 *
	 * Errors, if any can be accessed from hasError() and getErrors().
	 */
	public function execute() {
		$errors = array();
		$query = SMWQueryProcessor::createQuery( $this->queryString, $this->parameters,
			SMWQueryProcessor::SPECIAL_PAGE , $this->parameters['format'], $this->printOuts );
		$res = smwfGetStore()->getQueryResult( $query );
		$this->queryResult = $res;

		$errors = array_merge( $errors, $res->getErrors() );
		if ( !empty( $errors ) ) {
			$this->errorsOccured = true;
			$this->errors = array_merge( $errors, $this->errors );
		}

		// BEGIN: Try to be smart for rss/ical if no description/title is given and we have a concept query
		if ( $this->parameters['format'] == 'rss' ) {
			$descKey = 'rssdescription';
			$titleKey = 'rsstitle';
		} elseif ( $this->parameters['format'] == 'icalendar' ) {
			$descKey = 'icalendardescription';
			$titleKey = 'icalendartitle';
		} else {
			$descKey = false;
		}

		if ( $descKey && ( $query->getDescription() instanceof SMWConceptDescription ) &&
			 ( !isset( $this->parameters[$descKey] ) || !isset( $this->parameters[$titleKey] ) ) ) {
			$concept = $query->getDescription()->getConcept();

			if ( !isset( $this->parameters[$titleKey] ) ) {
				$this->parameters[$titleKey] = $concept->getText();
			}

			if ( !isset( $this->parameters[$descKey] ) ) {
				// / @bug The current SMWStore will never return SMWConceptValue (an SMWDataValue) here; it might return SMWDIConcept (an SMWDataItem)
				$dv = end( smwfGetStore()->getPropertyValues( SMWWikiPageValue::makePageFromTitle( $concept ), new SMWDIProperty( '_CONC' ) ) );
				if ( $dv instanceof SMWConceptValue ) {
					$this->parameters[$descKey] = $dv->getDocu();
				}
			}
		}
		// END: Try to be smart for rss/ical if no description/title is given and we have a concept query
	}

	/**
	 * Returns the results in HTML, or in case of exports, a link to the
	 * result.
	 *
	 * This method can only be called after execute() has been called.
	 *
	 * @return string of all the HTML generated
	 */
	public function getHTMLResult() {
		$result = '';

		$res = $this->queryResult;
		$printer = SMWQueryProcessor::getResultPrinter( $this->parameters['format'],
			SMWQueryProcessor::SPECIAL_PAGE );
		$resultMime = $printer->getMimeType( $res );

		if ( $res->getCount() > 0 ) {
			$queryResult = $printer->getResult( $res, $this->parameters, SMW_OUTPUT_HTML );

			if ( is_array( $queryResult ) ) {
				$result .= $queryResult[0];
			} else {
				$result .= $queryResult;
			}
		} else {
			$result = wfMsg( 'smw_result_noresults' );
		}

		return $result;
	}

	/**
	 * Returns the query in the #ask format
	 *
	 * @return string
	 */
	public function getAsk() {
		$result = '{{#ask:' . htmlspecialchars( $this->queryString ) . "\n";
		foreach ( $this->printOuts as $printout ) {
			$result .= '|' . $printout->getSerialisation() . "\n";
		}
		foreach ( $this->parameters as $param_name => $param_value ) {
			$result .= '|' . htmlspecialchars( $param_name ) .
				'=' . htmlspecialchars( $param_value ) . "\n";
		}
		$result .= '}}';
		return $result;
	}

	/**
	 * Returns the query.
	 *
	 * @return string
	 */
	public function getQueryString() {
		return $this->queryString;
	}

	/**
	 * Returns number of available results.
	 *
	 * @return int
	 */
	public function getResultCount() {
		if ( !is_null( $this->queryResult ) ) {
			return $this->queryResult->getCount();
		} else {
			return 0;
		}
	}

	/**
	 * Returns the parameter array.
	 *
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * Returns additional prinouts as an array of SMWPrintRequests.
	 *
	 * @return array SMWPrintRequest or an empty array
	 */
	public function getPrintOuts() {
		if ( !empty( $this->printOuts ) &&
		( $this->printOuts[0] instanceof SMWPrintRequest ) ) {
			return $this->printOuts;
		}
		return array();
	}

	/**
	 * Constructs a new SMWQueryUIHelper object when the query is passed to
	 * the UI in the Info-link format. This constructor should be used for
	 * handling the "further results" links in wiki-pages that use #ask. If
	 * your search UI handles form parameters only, then consider using
	 * makeForUI().
	 *
	 * If any errors do occur while parsing parameters, they may be accessed
	 * from hasError() and getErrors().
	 *
	 * @param string $p parameters of the query.
	 * @param boolean $enableValidation
	 * @return SMWQueryUIHelper
	 *
	 * @todo Handle validation for infolink parameters
	 */
	public static function makeForInfoLink( $p, $enableValidation = false ) {
		$result = new SMWQueryUIHelper( self::WIKI_LINK );
		$result->extractParameters( $p );
		$result->execute();
		return $result;
	}

	/**
	 * Constructs a new SMWQueryUIHelper when the query is passed to the UI
	 * from a web form. This constructor should be used to handle form
	 * parameters sent from the UI itself. If your search UI must also handle
	 * "further results" links from a wiki page, consider using
	 * makeForInfoLink().
	 *
	 * If any errors do occur while parsing parameters, they may be accessed
	 * from hasError() and getErrors().
	 *
	 * @param string $query
	 * @param array $params of key=>value pairs
	 * @param array $printouts array of '?property' strings
	 * @param boolean $enableValidation
	 * @return SMWQueryUIHelper
	 *
	 */
	public static function makeForUI( $query, array $params, array $printouts, $enableValidation = false ) {
		$result = new SMWQueryUIHelper( self::SPECIAL_PAGE );
		$result->setParams( $params, $enableValidation );
		$result->setPrintOuts( $printouts, $enableValidation );
		$result->setQueryString( $query, $enableValidation );
		$result->extractParameters( '' );
		// $result->execute();
		return $result;
	}

	/**
	 * Checks if $property exists in the wiki or not.
	 *
	 * @param string $property a property name in "?property" format
	 * @return boolean
	 */
	protected static function validateProperty( $property ) {
		/*
		 * Curently there isn't a simple, back-end agnost way of searching for properties from
		 * SMWStore. We hence we check if $property has a corresponding page describing it.
		 */
		$prop = substr( $property, 1 ); // removing the leading '?' while checking.
		$property_page = Title::newFromText( $prop, SMW_NS_PROPERTY );
		if ( $property_page instanceof Title ) {
			return( $property_page->exists() );
		} else {
			return false;
		}
	}

}
