<?php

/**
 * A base class for Semantic Search UIs. All Semantic Search UI's may subclass
 * from this.
 *
 * The commonly used and overloaded methods are the ones which create some
 * default UI elements (the getxxxFormBox() methods) and corresponding methods
 * that extract data from them (the processxxxFormBox() methods).
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
abstract class SMWQueryUI extends SMWQuerySpecialPage {
	/**
	 * The handle for the underlying SMWQueryUIHelper class.
	 * @var SMWQueryUIHelper
	 * @see SMWQueryUIHelper
	 */
	protected $uiCore;

	const ENABLE_AUTO_SUGGEST = true;
	const DISABLE_AUTO_SUGGEST = false;

	/**
	 * Initialises the page. Sets the property $uiCore to the appropriate
	 * helper object.
	 *
	 * To create a custom UI, adding changes to makePage() is usually
	 * enough, but one might want to overload this method to get better
	 * handling of form parameters.
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
			$wgOut->addHTML( '<br />' . wfMessage( 'smw_iq_disabled' )->escaped() );
			return;
		}

		// Check if this request is actually an AJAX request, and handle it accodingly:
		$ajaxMode = $this->processFormatOptions( $wgRequest );

		// If not replying to AJAX, build the UI HTML as usual:
		if ( !$ajaxMode ) {
			// Checking if a query string has been sent by using the form:
			if ( $this->processQueryFormBox( $wgRequest ) !== false ) {
				$params = $this->processParams();
				$this->uiCore =  SMWQueryUIHelper::makeForUI(
					$this->processQueryFormBox( $wgRequest ),
					$params, array(), false );
				if ( $this->uiCore->getQueryString() !== '' ) {
					$this->uiCore->execute();
				}
			} else { // Query not sent via form (though maybe from "further results" link:
				$this->uiCore =  SMWQueryUIHelper::makeForInfoLink( $p );
			}

			// Add RSS feed of results to the page head:
			if ( $this->isSyndicated() &&
					$this->uiCore->getQueryString() !== '' &&
					// Remove next line when MW 1.15 is no longer supported by SMW:
					method_exists( $wgOut, 'addFeedlink' ) &&
					array_key_exists( 'rss', $wgFeedClasses ) ) {
				$res = $this->uiCore->getResultObject();
				$link = $res->getQueryLink();
				$link->setParameter( 'rss', 'format' );
				$link->setParameter( $this->uiCore->getLimit(), 'limit' );
				$wgOut->addFeedLink( 'rss', $link->getURl() );
			}

			$wgOut->addHTML( $this->makePage( $p ) );
		}

		// Make sure locally collected output data is pushed to the output:
		SMWOutputs::commitToOutputPage( $wgOut );
	}

	/**
	 * This method should return an associative array of parameters
	 * extracted from the current (global) web request.
	 *
	 * Implementations can call the various processXXXBox() methods for
	 * reading parameters that belong to standard UI elements provided by
	 * this base class (by according getXXXBox() methods).
	 *
	 * @return array of parameters
	 */
	protected abstract function processParams();

	/**
	 * Create an HTML form that is to be displayed on the page and return
	 * the according HTML code.
	 *
	 * @param string $p the sub-page string
	 * @return string HTML code for the page
	 */
	protected abstract function makePage( $p );

	/**
	 * To enable/disable syndicated feeds of results to appear in the UI
	 * header.
	 *
	 * @return boolean
	 */
	public function isSyndicated() {
		return true;
	}

	/**
	 * Builds a read-only #ask embed code of the given query. The code is
	 * presented in html code.
	 *
	 * @return string
	 */
	protected function getAskEmbedBox() {
		$result = '';
		if ( $this->uiCore->getQueryString() != "" ) {
			$result = Html::rawElement(
				'div',
				array( 'id' => 'inlinequeryembed' ),
				Html::rawElement(
					'div',
					array( 'id' => 'inlinequeryembedinstruct' ),
					wfMessage( 'smw_ask_embed_instr' )->escaped()
				) .
				Html::element( 'textarea',
					array( 'id' => 'inlinequeryembedarea',
						'readonly' => 'yes',
						'cols' => '20',
						'rows' => '6',
						'onclick' => 'this.select()'
					),
				$this->uiCore->getAsk() )
			);
		}
		return $result;
	}

	/**
	 * A function which formats the current errors in HTML.
	 */
	protected function getErrorsHtml() {
		$result = Html::openElement( 'ul' );
		$errors = $this->uiCore->getErrors();
		foreach ( $errors as $error ) {
			$result .= '<span class="smwwarning"><li>' . $error . '</li></span>';
		}
		$result .= '</ul>';
		return $result;
	}

	/**
	 * Enable auto completion scripts and styles.
	 */
	protected function enableAutocompletion() {
		SMWOutputs::requireResource( 'jquery.ui.autocomplete' );

		$javascriptAutocompleteText = <<<END
<script type="text/javascript">
	function smw_split( val ) {
		return val.split( '\\n' );
	}

	function smw_extractLast( term ) {
		return smw_split( term ).pop();
	}

	function smw_escapeQuestion( term ){
		if ( term.substring( 0, 1 ) == "?" ) {
			return term.substring( 1 );
		} else {
			return term;
		}
	}

/* extending jQuery functions for custom highligting */
	jQuery.ui.autocomplete.prototype._renderItem = function( ul, item ) {
		var term_without_q = smw_escapeQuestion( smw_extractLast( this.term ) );
		var re = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term_without_q.replace("/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi", "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi");
		var loc = item.label.search( re );
		if ( loc >= 0 ) {
			var t = item.label.substr( 0, loc ) + '<strong>' + item.label.substr( loc, term_without_q.length ) + '</strong>' + item.label.substr( loc + term_without_q.length );
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
		filter: function( array, term ) {
			var matcher = new RegExp( "\\\b" + jQuery.ui.autocomplete.escapeRegex( term ), "i" );
			return jQuery.grep( array, function( value ) {
				return matcher.test( value.label || value.value || value );
			} );
		}
	} );
</script>
END;

		SMWOutputs::requireScript( 'smwAutocompleteQueryUICore', $javascriptAutocompleteText );

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
		// Prepare navigation bar.
		if ( $offset > 0 ) {
			$this->setUrlArgs(
				array( 'offset' => max( 0, $offset - $limit ), 'limit' => $limit )
			);
			$navigation = Html::element(
				'a',
				array(
					'href' => $this->getTitle()->getLocalURL( wfArrayToCGI( $this->getUrlArgs() ) ),
					'rel' => 'nofollow'
				),
				wfMessage( 'smw_result_prev' )->text()
			);

		} else {
			$navigation = wfMessage( 'smw_result_prev' )->text();
		}

		// @otdo FIXME: i18n patchwork.
		$navigation .=
			'&#160;&#160;&#160;&#160; <b>' .
				wfMessage( 'smw_result_results' )->text() . ' ' . $wgLang->formatNum( $offset + 1 ) .
			' - ' .
				$wgLang->formatNum( $offset + $this->uiCore->getResultCount() ) .
			'</b>&#160;&#160;&#160;&#160;';

		if ( $hasFurtherResults ) {
			$this->setUrlArgs(
				array( 'offset' => max( 0, $offset + $limit ), 'limit' => $limit )
			);
			$navigation .= Html::element(
				'a',
				array(
					'href' => $this->getTitle()->getLocalURL( wfArrayToCGI( $this->getUrlArgs() ) ),
					'rel' => 'nofollow'
				),
				wfMessage( 'smw_result_next' )->text()
			);
		} else {
			$navigation .= wfMessage( 'smw_result_next' )->text();
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
				$this->setUrlArgs( array( 'offset' => $offset, 'limit' => $l ) );
				$navigation .= Html::element(
					'a',
					array(
						'href' => $this->getTitle()->getLocalURL( wfArrayToCGI( $this->getUrlArgs() ) ),
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
	 * @global string $smwgScriptPath
	 * @return string
	 */
	protected function getQueryFormBox() {
		$this->setUrlArgs( array( 'q' => $this->uiCore->getQueryString() ) );
		$result = '<div>' .
			Html::element( 'textarea',
				array( 'name' => 'q', 'id' => 'querybox', 'rows'=>'3' ),
				$this->uiCore->getQueryString()
			) .
			'</div>';
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

		$mainLabel = $wgRequest->getVal( 'pmainlabel', '' );
		$params['mainlabel'] = $mainLabel;

		// processing params for main result column
		if ( is_array( $mainColumnLabels ) ) {
			foreach ( $mainColumnLabels as $key => $label ) {
				if ( $label === '' ) {
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
				if ( trim( $value ) === '' ) {
					$po[$key] = "?$categoryNamespace" ;
				} else {
					$po[$key] = "?$categoryNamespace:$value";
					if ( is_array( $categoryYesValues )
						&& is_array( $categoryNoValues )
						&& array_key_exists( $key, $categoryYesValues )
						&& array_key_exists( $key, $categoryNoValues ) )
					{
						if ( $categoryYesValues[$key] !== ''
							&& $categoryNoValues[$key] !== '' )
						{
							$po[$key] .= "#$categoryYesValues[$key],$categoryNoValues[$key]";
						}
					}
				}
			}
		}
		if ( is_array( $categoryLabelValues ) ) {
			foreach ( $categoryLabelValues as $key => $value ) {
				if ( trim( $value ) !== '' ) {
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
				if ( $propertyValues[$key] === '' ) {
					unset( $propertyValues[$key] );
				}
				if ( $smwgQSortingSupport
					&& is_array( $orderValues )
					&& array_key_exists( $key, $orderValues )
					&& $orderValues[$key] != 'NONE' )
				{
					$params['sort'] .= ( $params['sort'] !== '' ? ',':'' ) . $propertyValues[$key];
					$params['order'] .= ( $params['order'] !== '' ? ',':'' ) . $orderValues[$key];
				}
			}
			if ( $params['sort'] === '' ) {
				unset ( $params['sort'] );
			}
			if ( $params['order'] === '' ) {
				unset ( $params['order'] );
			}
			$displayValues = $wgRequest->getArray( 'display' );
			if ( is_array( $displayValues ) ) {
				foreach ( $displayValues as $key => $value ) {
					if ( $value == '1' && array_key_exists( $key, $propertyValues ) ) {
						$propertyValues[$key] = '?' . trim( $propertyValues[$key] ); // adding leading '?'
						if ( is_array( $propertyFormatValues ) // adding PO format
							&& array_key_exists( $key, $propertyFormatValues )
							&& $propertyFormatValues[$key] !== '' )
						{
							$propertyValues[$key] .= '#' . $propertyFormatValues[$key];
						}
						if ( is_array( $propertyLabelValues ) // adding label
							&& array_key_exists( $key, $propertyLabelValues )
							&& $propertyLabelValues[$key] !== '' )
						{
							$propertyValues[$key] .= ' = ' . $propertyLabelValues[$key];
						}
						if ( is_array( $propertyLimitValues ) // adding limit
							&& array_key_exists( $key, $propertyLimitValues )
							&& $propertyLimitValues[$key] !== '' )
						{
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
		if ( $smwgQSortingSupport ) {
			$po = array_merge( $params, $po );
		}
		return $po;
	}

	/**
	 * Generates the forms elements(s) for choosing printouts and sorting
	 * options. Use its complement processPoSortFormBox() to decode data
	 * sent by these elements.
	 *
	 * @global boolean $smwgQSortingSupport
	 * @global boolean $smwgQRandSortingSupport
	 * @global WebRequest $wgRequest
	 * @global string $smwgScriptPath
	 * @global integer $smwgQPrintoutLimit
	 * @param mixed $enableAutocomplete
	 * @return string
	 */
	protected function getPoSortFormBox( $enableAutocomplete = SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
		global $smwgQSortingSupport, $wgRequest, $smwgScriptPath;
		global $smwgQRandSortingSupport, $smwgQPrintoutLimit;

		SMWOutputs::requireResource( 'jquery.ui.autocomplete' );
		SMWOutputs::requireResource( 'jquery.ui.dialog' );
		SMWOutputs::requireResource( 'ext.smw.style' );

		$result = '<span id="smwposortbox">';
		$params = $this->uiCore->getParameters();

		// mainlabel
		if ( is_array( $params ) && array_key_exists( 'mainlabel', $params ) ) {
			$mainLabel = $params['mainlabel'];
		} else {
			$mainLabel = '';
		}
		if ( $mainLabel == '-' ) {
			$mainLabelText = '';
			$formDisplay = 'none';
		} else {
			$mainLabelText = $mainLabel;
			$formDisplay = 'block';
		}
		$result .= Html::openElement(
				'div',
				array( 'id' => 'smwmainlabel',
					'class' => 'smwsort',
					'style' => "display:$formDisplay;" )
			) .
			Html::openElement( 'span',
				array( 'class' => 'smwquisortlabel' ) ) .
			Html::openElement( 'span',
				array( 'class' => 'smw-remove' ) ) .
			Html::openElement( 'a',
				array( 'href' => 'javascript:smwRemoveMainLabel()' ) ) .
			'<img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMessage( 'smw_qui_delete' )->text() . '">' .
			'</a>' .
			'</span><strong>' .
			wfMessage( 'smw_qui_rescol' )->text() .
			'</strong></span>' .
			Xml::openElement( 'div',
				array( 'id' => 'mainlabel-dialog',
					'title' => wfMessage( 'smw_qui_mainlabopts' )->text(),
					'class' => 'smwmainlabdialog' )
				) .
			'<table align="center" ><tr>' .
				'<td>' . wfMessage( 'smw_qui_dlabel' )->text() . '</td>' .
				'<td><input size="25" value="' . $mainLabelText . '" id="mainlabelvis" /></td>' .
			'</tr></table>' .
			'</div>' .
			'<input type="hidden" name="pmainlabel" value="' . $mainLabel . '" id="mainlabelhid" /> ' .
			'<a class="smwq-more" href="javascript:smw_makeMainlabelDialog()">' . wfMessage( 'smw_qui_options' )->text() . '</a> ' .
			'</div>';
		$urlArgs = array();
		$urlArgs['pmainlabel'] = $mainLabel;

		// START: create form elements already submitted earlier via form
		// attempting to load parameters from $wgRequest
		$propertyValues = $wgRequest->getArray( 'property', array() );
		$propertyLabelValues = $wgRequest->getArray( 'prop_label', array() );
		$propertyFormatValues = $wgRequest->getArray( 'prop_format', array() );
		$propertyLimitValues = $wgRequest->getArray( 'prop_limit', array() );
		$orderValues = $wgRequest->getArray( 'order', array() );
		$displayValues = $wgRequest->getArray( 'display', array() );
		$categoryValues = $wgRequest->getArray( 'category', array() );
		$categoryLabelValues = $wgRequest->getArray( 'cat_label', array() );
		$categoryYesValues = $wgRequest->getArray( 'cat_yes', array() );
		$categoryNoValues = $wgRequest->getArray( 'cat_no', array() );
		$mainColumnLabels = $wgRequest->getArray( 'maincol_label', array() );

		$mainLabelCheck = $wgRequest->getCheck( 'pmainlabel' );

		if ( !$mainLabelCheck ) {
			/*
			 * Printouts and sorting might be set via another widget/form/source, so
			 * create elements by fetching data from $uiCore. The exact ordering
			 * of Ui elements might not be preserved, if the above check were to
			 * be removed.
			 */
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
			foreach ( $printOuts as $poValue ) {
				if ( $poValue->getMode() == SMWPrintRequest::PRINT_CATS ) {
					$categoryValues[$counter] = ' ';
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

						$propertyLabelValues[$counter] =
							( $poValue->getLabel() == $propertyValues[$counter] ) ? '':$poValue->getLabel();

						$propertyFormatValues[$counter] = $poValue->getOutputFormat();

						$propertyLimitValues[$counter] =
							( $poValue->getParameter( 'limit' ) ) ? $poValue->getParameter( 'limit' ):'';

						$orderValues[$counter] = $orderVal[0];
						$displayValues[$counter] = '1';
						$counter++;
						array_shift( $orderVal );
						array_shift( $sortVal );
					} else {
						$propertyValues[$counter] = $poValue->getData()->getText();

						$propertyLabelValues[$counter] =
							( $poValue->getLabel() == $propertyValues[$counter] ) ? '':$poValue->getLabel();

						$propertyFormatValues[$counter] = $poValue->getOutputFormat();

						$propertyLimitValues[$counter] =
							( $poValue->getParameter( 'limit' ) ) ? $poValue->getParameter( 'limit' ):'';

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

		$keys = array_keys( $propertyValues );
		foreach ( $keys as $value ) {
			$additionalPOs[$value] = $propertyValues[$value];
			// array_merge won't work because numeric keys need to be preserved
		}


		$keys = array_keys( $categoryValues );
		foreach ( $keys as $value ) {
			$additionalPOs[$value] = $categoryValues[$value];
			// array_merge won't work because numeric keys need to be preserved
		}

		$keys = array_keys( $mainColumnLabels );
		foreach ( $keys as $value ) {
			$additionalPOs[$value] = $mainColumnLabels[$value];
			// array_merge won't work because numeric keys need to be preserved
		}

		ksort( $additionalPOs );
		foreach ( $additionalPOs as $key => $value ) {
			if ( array_key_exists( $key, $propertyValues ) ) {
				/*
				 * Make an element for additional properties
				 */
				$result .= Html::openElement(
					'div',
					array( 'id' => "sort_div_$i", 'class' => 'smwsort' )
				);

				$result .= '<span class="smwquisortlabel">' .
					'<span class="smw-remove">' .
					'<a href="javascript:removePOInstance(\'sort_div_' . $i . '\')">' .
					'<img src="' . $smwgScriptPath . '/skins/images/close-button.png"' .
						'alt="' . wfMessage( 'smw_qui_delete' )->text() .
					'">' .
					'</a></span>';
				$result .= wfMessage( 'smw_qui_property' )->text() . '</span>';

				$result .= Html::input( 'property[' . $i . ']',
					$propertyValues[$key],
					'text',
					array( 'size' => '25', 'id' => "property$i" ) ) .
				"\n";

				$urlArgs["property[$i]"] = $propertyValues[$key];
				if ( $smwgQSortingSupport ) {
					$result .= Html::openElement(
						'select',
						array( 'name' => "order[$i]" )
					);
					if ( array_key_exists( $key, $orderValues ) ) {
						$urlArgs["order[$i]"] = $orderValues[$key];
					}
					$if1 = ( !array_key_exists( $key, $orderValues ) || $orderValues[$key] == 'NONE' );
					$result .= Xml::option( wfMessage( 'smw_qui_nosort' )->text(), "NONE", $if1 );

					$if2 = ( array_key_exists( $key, $orderValues ) && $orderValues[$key] == 'ASC' );
					$result .= Xml::option( wfMessage( 'smw_qui_ascorder' )->text(), "ASC", $if2 );

					$if3 = ( array_key_exists( $key, $orderValues ) && $orderValues[$key] == 'DESC' );
					$result .= Xml::option( wfMessage( 'smw_qui_descorder' )->text(), "DESC", $if3 );

					if ( $smwgQRandSortingSupport ) {
						$if4 = ( array_key_exists( $key, $orderValues ) && $orderValues[$key] == 'RANDOM' );
						$result .= Xml::option( wfMessage( 'smw_qui_randorder' )->text(), "RANDOM", $if4 );
					}

					$result .= Xml::closeElement( 'select' );

					$if5 = ( array_key_exists( $key, $displayValues ) );
					$result .= Xml::checkLabel( wfMessage( 'smw_qui_shownresults' )->text(), "display[$i]", "display$i", $if5 );
					if ( $if5 ) {
						$urlArgs["display[$i]"] = '1';
					}
				}
				if ( array_key_exists( $key, $propertyLabelValues ) ) {
					$result .= Html::hidden(
						"prop_label[$i]",
						$propertyLabelValues[$key],
						array( 'id' => "prop_label$i" )
					);
					$urlArgs["prop_label[$i]"] = $propertyLabelValues[$key];
				} else {
					$result .= Html::hidden( "prop_label[$i]",
						'',
						array( 'id' => "prop_label$i" )
					);
				}
				if ( array_key_exists( $key, $propertyFormatValues ) ) {
					$result .= Html::hidden( "prop_format[$i]",
						$propertyFormatValues[$key],
						array( 'id' => "prop_format$i" )
					);
					$urlArgs["prop_format[$i]"] = $propertyFormatValues[$key];
				} else {
					$result .= Html::hidden( "prop_format[$i]",
						'',
						array( 'id' => "prop_format$i" )
					);
				}
				if ( array_key_exists( $key, $propertyLimitValues ) ) {
					$result .= Html::hidden( "prop_limit[$i]",
						$propertyLimitValues[$key],
						array( 'id' => "prop_limit$i" )
					);
					$urlArgs["prop_limit[$i]"] = $propertyLimitValues[$key];
				} else {
					$result .= Html::hidden( "prop_limit[$i]",
						'',
						array( 'id' => "prop_limit$i" )
					);
				}
				$result .= Html::element( 'a',
					array( 'id' => "more$i",
						'class' => 'smwq-more',
						'href' => "javascript:smw_makePropDialog('$i')"
					),
					wfMessage( 'smw_qui_options' )->text()
				);

				$result .= Xml::closeElement( 'div' );
				$i++;
			}
			if ( array_key_exists( $key, $categoryValues ) ) {
				if ( !array_key_exists( $key, $categoryLabelValues ) ) {
					$categoryLabelValues[$key] = '';
				}
				if ( !array_key_exists( $key, $categoryYesValues ) ) {
					$categoryYesValues[$key] = '';
				}
				if ( !array_key_exists( $key, $categoryNoValues ) ) {
					$categoryNoValues[$key] = '';
				}
				/*
				 * Make an element for additional categories
				 */
				$result .= Html::openElement( 'div',
					array( 'id' => "sort_div_$i", 'class' => 'smwsort' )
				);
				$result .= '<span class="smwquisortlabel">' .
					'<span class="smw-remove">' .
					Html::openElement( 'a',
						array( 'href' => "javascript:removePOInstance('sort_div_$i')" )
					) .
					'<img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMessage( 'smw_qui_delete' )->text() . '">' .
					'</a>' .
					'</span>' .
					wfMessage( 'smw_qui_category' )->text() .
					'</span>' .
					Xml::input( "category[$i]",
						'25',
						$categoryValues[$key],
						array( 'id' => "category$i" )
					) . " " .
					Html::hidden( "cat_label[$i]",
						$categoryLabelValues[$key],
						array( 'id' => "cat_label$i" )
					) .
					Html::hidden( "cat_yes[$i]",
						$categoryYesValues[$key],
						array( 'id' => "cat_yes$i" )
					) .
					Html::hidden( "cat_no[$i]",
						$categoryNoValues[$key],
						array( 'id' => "cat_no$i" )
					) .
					Html::element( 'a',
						array( 'id' => "more$i",
							'class' => 'smwq-more',
							'href' => "javascript:smw_makeCatDialog('$i')"
						),
						wfMessage( 'smw_qui_options' )->text()
					) .
					Xml::closeElement( 'div' );
				$urlArgs["category[$i]"] =
					( $categoryValues[$key] === '' ) ? ' ':$categoryValues[$key];

				$urlArgs["cat_label[$i]"] = $categoryLabelValues[$key];
				$urlArgs["cat_yes[$i]"] = $categoryYesValues[$key];
				$urlArgs["cat_no[$i]"] = $categoryNoValues[$key];
				$i++;
			}
			if ( array_key_exists( $key, $mainColumnLabels ) ) {
				/*
				 * Make an element for main column aka query-matches
				 */
				$result .= Html::openElement( 'div',
						array( 'id' => "sort_div_$i", 'class' => 'smwsort' )
					) .
					'<span class="smwquisortlabel">' .
					'<span class="smw-remove">' .
					Html::openelement( 'a',
						array( 'href' => "javascript:removePOInstance('sort_div_$i')" )
					) .
					'<img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMessage( 'smw_qui_delete' )->text() . '">' .
					'</a>' .
					'</span><strong>' .
					wfMessage( 'smw_qui_rescol' )->text() .
					'</strong></span>' .
					Html::hidden( "maincol_label[$i]",
						$mainColumnLabels[$key],
						array ( 'id' => "maincol_label$i" )
					) . " " .
					'<a class="smwq-more" href="javascript:smw_makeQueryMatchesDialog(\'' . $i . '\')">' . wfMessage( 'smw_qui_options' )->text() . '</a> ' .
					'</div>';
				$urlArgs["maincol_label[$i]"] =
					( $mainColumnLabels[$key] === '' ) ? ' ':$mainColumnLabels[$key];
				$i++;
			}
		}
		$numSortValues = $i;
		$this->setUrlArgs( $urlArgs );
		// END: create form elements already submitted earlier via form

		// create hidden form elements to be cloned later
		// property
		$hiddenProperty = Html::openElement( 'div',
				array( 'id' => 'property_starter',
					'style' => 'display:none' )
				) .
			'<span class="smwquisortlabel">' .
			'<span class="smw-remove">' .
			'<a>' .
			'<img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMessage( 'smw_qui_delete' )->text() . '">' .
			'</a>' .
			'</span>' .
			wfMessage( 'smw_qui_property' )->text() .
			'</span>' .
			Xml::input( 'property_num', '25' ) . " " ;
		if ( $smwgQSortingSupport ) {
			$hiddenProperty .= Html::openElement( 'select', array( 'name' => 'order_num' ) ) .
					Xml::option( wfMessage( 'smw_qui_nosort' )->text(), 'NONE' ) .
					Xml::option( wfMessage( 'smw_qui_ascorder' )->text(), 'ASC' ) .
					Xml::option( wfMessage( 'smw_qui_descorder' )->text(), 'DESC' );
			if ( $smwgQRandSortingSupport ) {
				$hiddenProperty .= Xml::option( wfMessage( 'smw_qui_randorder' )->text(), 'RANDOM' );
			}
			$hiddenProperty .= Xml::closeElement( 'select' ) .
				Xml::checkLabel( wfMessage( 'smw_qui_shownresults' )->text(), "display_num", '', true );
		}
		$hiddenProperty .= Html::hidden( 'prop_label_num', '' ) .
			Html::hidden( 'prop_format_num', '' ) .
			Html::hidden( 'prop_limit_num', '' ) .
			Xml::closeElement( 'div' );
		$hiddenProperty = json_encode( $hiddenProperty );
		// category
		$hiddenCategory = Html::openElement( 'div',
			array( 'id' => 'category_starter',
				'style' => 'display:none' )
			) .
			'<span class="smwquisortlabel">' .
			'<span class="smw-remove">' .
			'<a>' .
			'<img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMessage( 'smw_qui_delete' )->text() . '">' .
			'</a>' .
			'</span>' .
			wfMessage( 'smw_qui_category' )->text() . '</span>' .
			Xml::input( "category_num", '25' ) . " " .
			'<input type="hidden" name="cat_label_num" />' .
			'<input type="hidden" name="cat_yes_num" />' .
			'<input type="hidden" name="cat_no_num" />' .
			Xml::closeElement( 'div' );
		$hiddenCategory = json_encode( $hiddenCategory );
		// For '?' printouts
		$hiddenMainColumn = Html::openElement( 'div',
			array( 'id' => 'maincol_starter',
				'style' => 'display:none' )
			) .
			'<span class="smwquisortlabel">' .
			'<span class="smw-remove">' .
			'<a>' .
			'<img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMessage( 'smw_qui_delete' )->text() . '">' .
			'</a>' .
			'</span><strong>' .
			wfMessage( 'smw_qui_rescol' )->text() . '</strong></span>' .
			Html::hidden( "maincol_label_num", '' ) . " " .
			Xml::closeElement( 'div' );
		$hiddenMainColumn = json_encode( $hiddenMainColumn );

		// Create dialog-boxes
		// create dialogbox for Property options
		$propertyHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_prop' )->text(),
			'',
			'd-property',
			'd-property'
		);
		$propertyLabelHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_labl' )->text(),
			'',
			'd-prop-label',
			'd-prop-label'
		);
		$propertyFormatHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_formt' )->text(),
			'',
			'd-prop-format',
			'd-prop-format'
		);
		$propertyLimitHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_limt' )->text(),
			'd-prop-limit',
			'd-prop-limit'
		);
		$propertyDialogBox = Xml::openElement(
				'div',
				array( 'id' => 'prop-dialog',
					'title' => wfMessage( 'smw_prp_options' )->text(),
					'class' => 'smwpropdialog' )
			) .
			'<table align="center">' .
			'<tr><td>' . $propertyHtml[0] .       '</td><td>' . $propertyHtml[1] . '</td></tr>' .
			'<tr><td>' . $propertyLabelHtml[0] .  '</td><td>' . $propertyLabelHtml[1] . '</td></tr>' .
			'<tr><td>' . $propertyLimitHtml[0] .  '</td><td>' . $propertyLimitHtml[1] . '</td></tr>' .
			'<tr><td>' . $propertyFormatHtml[0] . '</td><td>' . $propertyFormatHtml[1] . '</td></tr>' .
			'</table>' .
			Xml::closeElement( 'div' );

		// create dialogbox for Category options
		$categoryHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_dcategory' )->text(),
			'',
			'd-category',
			'd-category'
		);
		$categoryLabelHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_dlabel' )->text(),
			'',
			'd-category-label',
			'd-category-label'
		);
		$categoryYesHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_dcatyes' )->text(),
			'',
			'd-category-yes',
			'd-category-yes'
		);
		$categoryNoHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_dcatno' )->text(),
			'',
			'd-category-no',
			'd-category-no' );
		$categoryDialogBox = Xml::openElement( 'div',
			array( 'id' => 'cat-dialog',
				'title' => wfMessage( 'smw_qui_catopts' )->text(),
				'class' => 'smwcatdialog' )
			) .
			'<table align="center">' .
			'<tr><td>' . $categoryHtml[0] . '</td><td>' . $categoryHtml[1] . '</td></tr>' .
			'<tr><td>' . $categoryLabelHtml[0] . '</td><td>' . $categoryLabelHtml[1] . '</td></tr>' .
			'</table><br/><table align="center">' .
			'<tr><td>' . $categoryYesHtml[0] . '</td><td>' . $categoryYesHtml[1] . '</td></tr>' .
			'<tr><td>' . $categoryNoHtml[0] . '</td><td>' . $categoryNoHtml[1] . '</td></tr>' .
			'</table>' .
			Xml::closeElement( 'div' );

		// Create dialog box for QueryMatches
		$mainResLabelHtml = Xml::inputLabelSep( wfMessage( 'smw_qui_dlabel' )->text(), '', 'd-mainres-label' );
		$mainResDialogBox = Xml::openElement( 'div',
			array( 'id' => 'mainres-dialog',
				'title' => wfMessage( 'smw_qui_mainlabopts' )->text(),
				'class' => 'smwmainlabdialog' )
			) .
			'<table align="center">' .
			'<tr><td>' . $mainResLabelHtml[0] . '</td><td>' . $mainResLabelHtml[1] . '</td></tr>' .
			'</table>' .
			Xml::closeElement( 'div' );

		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '[<a href="javascript:smw_addPropertyInstance(\'property_starter\', \'sorting_main\')">' . wfMessage( 'smw_qui_addnprop' )->text() . '</a>]' .
					'[<a href="javascript:smw_addCategoryInstance(\'category_starter\', \'sorting_main\')">' . wfMessage( 'smw_qui_addcategory' )->text() . '</a>]' .
					'[<a href="javascript:smw_addMainColInstance(\'maincol_starter\', \'sorting_main\')">' . wfMessage( 'smw_qui_addrescol' )->text() . '</a>]' .
					"\n";

		// Javascript code for handling adding and removing the "sort" inputs
		if ( $enableAutocomplete == SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
			$this->enableAutocompletion();
		}
		// localisation messages for javascript
		$optionsMsg = wfMessage( 'smw_qui_options' )->text();
		$okMsg = wfMessage( 'smw_qui_ok' )->text();
		$cancelMsg = wfMessage( 'smw_qui_cancel' )->text();
		$javascriptText = <<<EOT
<script type="text/javascript">
	var num_elements = {$numSortValues};
	var smwgQPrintoutLimit={$smwgQPrintoutLimit};
EOT;
// add autocomplete
		if ( $enableAutocomplete == SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
			$javascriptText .= <<<EOT

	function smw_property_autocomplete(){
		jQuery( '[name*="property"]' ).autocomplete( {
			minLength: 2,
			source: function( request, response ) {
				url = wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['property']+'&format=jsonfm';

				jQuery.getJSON( url, 'search='+request.term, function( data ) {
					//remove the namespace prefix 'Property:' from returned data
					for( i=0; i < data[1].length; i++ ){
						data[1][i]=data[1][i].substr(data[1][i].indexOf(':')+1);
					}
					response(data[1]);
				});
			}
		} );
	}

	function smw_category_autocomplete(){
			jQuery( '[name*="category"]' ).autocomplete( {
			minLength: 2,
			source: function(request, response) {
				url = wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['category']+'&format=jsonfm';

				jQuery.getJSON( url, 'search='+request.term, function( data ){
					//remove the namespace prefix 'Category:' from returned data
					for( i=0; i<data[1].length; i++ ){
						data[1][i]=data[1][i].substr(data[1][i].indexOf(':')+1);
					}
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

	function smw_makeMainlabelDialog(){
		jQuery('#mainlabel-dialog').dialog("open");
	}

	function smwRemoveMainLabel(){
			jQuery( '#mainlabelhid' ).attr( 'value', '-' );
			jQuery( '#mainlabelvis' ).attr( 'value', '' );
			jQuery( '#smwmainlabel' ).hide();
	}


	function smw_makeQueryMatchesDialog( qm_id ){
		qmLabel=jQuery('#maincol_label'+qm_id).attr('value');
		jQuery('#d-mainres-label').attr('value', qmLabel);
		jQuery( '#mainres-dialog' ).dialog.sortid = qm_id;
		jQuery( '#mainres-dialog' ).dialog( 'open' );
	}

	function smw_makeCatDialog( cat_id ){
		jQuery( '#prop-cat input' ).attr('value','');

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
		if( jQuery( '.smwsort' ).length > smwgQPrintoutLimit ) return;

		var starter_div = document.getElementById(starter_div_id);
		var main_div = document.getElementById(main_div_id);

		//Create the new instance
		var new_div = starter_div.cloneNode(true);
		var div_id = 'sort_div_' + num_elements;
		new_div.id = div_id;
		new_div.style.display = 'block';
		jQuery(new_div.getElementsByTagName('label')).attr('for', 'display'+num_elements);
		jQuery(new_div).addClass( 'smwsort' );
		var children = new_div.getElementsByTagName('*');
		var x;
		for (x = 0; x < children.length; x++) {
			if (jQuery(children[x]).attr('for')) jQuery(children[x]).attr('for',"display"+num_elements);
			if (children[x].name){
				children[x].id = children[x].name.replace(/_num/, ''+num_elements);
				children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
			}
		}

		//Create 'options' link
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
		if( jQuery( '.smwsort' ).length > smwgQPrintoutLimit ) return;

		var starter_div = document.getElementById(starter_div_id);
		var main_div = document.getElementById(main_div_id);

		//Create the new instance
		var new_div = starter_div.cloneNode(true);
		var div_id = 'sort_div_' + num_elements;
		new_div.id = div_id;
		new_div.style.display = 'block';
		jQuery(new_div.getElementsByTagName('label')).attr('for', 'display'+num_elements);
		jQuery(new_div).addClass( 'smwsort' );
		var children = new_div.getElementsByTagName('*');
		var x;
		for (x = 0; x < children.length; x++) {
			if (jQuery(children[x]).attr('for')) jQuery(children[x]).attr('for',"display"+num_elements);
			if (children[x].name){
				children[x].id = children[x].name.replace(/_num/, ''+num_elements);
				children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
			}
		}

		//Create 'options' link
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
		if( (jQuery('#smwmainlabel').css('display')=='none')
			&& (jQuery('.smwsort').length==1)
		){
			jQuery('#mainlabelhid').attr('value','');
			jQuery('#mainlabelvis').attr('value','');
			jQuery('#smwmainlabel').show();
		} else {
			if( jQuery( '.smwsort' ).length > smwgQPrintoutLimit ){
				return;
			}
			var starter_div = document.getElementById(starter_div_id);
			var main_div = document.getElementById(main_div_id);

			//Create the new instance
			var new_div = starter_div.cloneNode(true);
			var div_id = 'sort_div_' + num_elements;
			new_div.id = div_id;
			new_div.style.display = 'block';
			jQuery(new_div.getElementsByTagName('label')).attr('for', 'display'+num_elements);
			jQuery(new_div).addClass( 'smwsort' );
			var children = new_div.getElementsByTagName('*');
			var x;
			for (x = 0; x < children.length; x++) {
				if (jQuery(children[x]).attr('for')) jQuery(children[x]).attr('for',"display"+num_elements);
				if (children[x].name){
					children[x].id = children[x].name.replace(/_num/, ''+num_elements);
					children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
				}
			}
			//Create 'options' link
			var more_button =document.createElement('span');
			more_button.innerHTML = ' <a class="smwq-more" href="javascript:smw_makeQueryMatchesDialog(\'' + num_elements + '\')">{$optionsMsg}</a> ';
			more_button.id = 'more'+num_elements;
			new_div.appendChild(more_button);

			//Add the new instance
			main_div.appendChild(new_div);

			// initialize delete button
			st='sort_div_'+num_elements;
			jQuery('#'+new_div.id).find(".smw-remove a")[0].href="javascript:removePOInstance('"+st+"')";
			num_elements++;
		}
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
		jQuery('$mainResDialogBox').appendTo(document.body);

		jQuery( '#mainlabel-dialog' ).dialog( {
			autoOpen: false,
			modal: true,
			resizable: true,
			minWidth: 400,
			buttons: {
				"{$okMsg}": function(){
					jQuery('#mainlabelhid').attr('value',jQuery('#mainlabelvis').attr('value'));
					jQuery(this).dialog("close");
				},
				"{$cancelMsg}": function(){
					jQuery(this).dialog("close");
				}
			}
		} );

		jQuery( '#mainres-dialog' ).dialog( {
			autoOpen: false,
			modal: true,
			resizable: true,
			minWidth: 400,
			buttons: {
				"{$okMsg}": function(){
					id = jQuery( this ).dialog.sortid;
					label = jQuery('#d-mainres-label');
					jQuery('#maincol_label'+id).attr('value', label);
					jQuery(this).dialog("close");
				},
				"{$cancelMsg}": function(){
					jQuery(this).dialog("close");
				}
			}
		} );

		jQuery( '#cat-dialog' ).dialog( {
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
			minWidth: 400,
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

		SMWOutputs::requireScript( 'smwAutocompleteQueryUI', $javascriptText );
		$result .= '</span>';
		return $result;
	}

	/**
	 * Generates the forms elements(s) for adding sorting options. Use its
	 * complement processSortingFormBox() to decode sorting data sent
	 * by these elements.
	 *
	 * @global boolean $smwgQSortingSupport
	 * @global WebRequest $wgRequest
	 * @return string
	 *
	 * @todo This code is not used anywhere in SMW.
	 */
	protected function getSortingFormBox() {
		global $smwgQSortingSupport, $wgRequest;

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
			$urlArgs = array();
			$result .=  "<div id=\"sort_div_$i\">" . wfMessage( 'smw_ask_sortby' )->text() . ' <input type="text" name="sort[' . $i . ']" value="' .
					htmlspecialchars( $sorts[$i] ) . "\" size=\"25\"/>\n" . '<select name="order[' . $i . ']"><option ';
			$urlArgs["sort[$i]"] = htmlspecialchars( $sorts[$i] );
			$urlArgs["order[$i]"] = $order;
				if ( $order == 'ASC' ) $result .= 'selected="selected" ';
			$result .=  'value="ASC">' . wfMessage( 'smw_qui_ascorder' )->text() . '</option><option ';
				if ( $order == 'DESC' ) $result .= 'selected="selected" ';

			$result .=  'value="DESC">' . wfMessage( 'smw_qui_descorder' )->text() . "</option></select>\n";
			$result .= '[<a class="smwq-remove" href="javascript:removeInstance(\'sort_div_' . $i . '\')">' . wfMessage( 'smw_qui_delete' )->text() . '</a>]' . "\n";
			$result .= "</div>\n";
			$this->setUrlArgs( $urlArgs );
		}

		$hidden =  '<div id="sorting_starter" style="display: none">' . wfMessage( 'smw_ask_sortby' )->text() . ' <input type="text" size="25" />' . "\n";
		$hidden .= ' <select name="order_num">' . "\n";
		$hidden .= '	<option value="ASC">' . wfMessage( 'smw_qui_ascorder' )->text() . "</option>\n";
		$hidden .= '	<option value="DESC">' . wfMessage( 'smw_qui_descorder' )->text() . "</option>\n</select>\n";
		$hidden .= "</div>\n";
		$hidden = json_encode( $hidden );

		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '<a href="javascript:addInstance(\'sorting_starter\', \'sorting_main\')">' . wfMessage( 'smw_add_sortcondition' )->text() . '</a>' . "\n";

		$num_sort_values = 0;

		if  ( !array_key_exists( 'sort', $params ) ) {
			$sort_values = $wgRequest->getArray( 'sort' );
			if ( is_array( $sort_values ) ) {
				$params['sort'] = implode( ',', $sort_values );
				$num_sort_values = count( $sort_values );
			}
		}
		// Javascript code for handling adding and removing the "sort" inputs
		$delete_msg = wfMessage( 'smw_qui_delete' )->text();

		SMWOutputs::requireResource( 'jquery' );
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

		SMWOutputs::requireScript( 'smwPrintoutControlsQueryUI', $javascriptText );
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
				if ( $order_value === '' ) {
					$order_value = 'ASC';
				}
				$params['order'] .= ( $params['order'] !== '' ? ',' : '' ) . $order_value;
			}
		}

		$sort_values = $wgRequest->getArray( 'sort' );
		if ( is_array( $sort_values ) ) {
			$params['sort'] = '';
			foreach ( $sort_values as $sort_value ) {
				$params['sort'] .= ( $params['sort'] !== '' ? ',' : '' ) . $sort_value;
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
		if ( $enableAutocomplete ) {
			$this->enableAutocompletion();
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

			SMWOutputs::requireScript( 'smwPrintoutAutocompleteQueryUI', $javascriptAutocompleteText );

		}
		$this->setUrlArgs( array( 'po' => $this->getPOStrings() ) );
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

		if ( $postring !== '' ) { // parameters from HTML input fields
			$ps = explode( "\n", $postring ); // params separated by newlines here (compatible with text-input for printouts)

			foreach ( $ps as $param ) { // add initial ? if omitted (all params considered as printouts)
				$param = trim( $param );

				if ( ( $param !== '' ) && ( $param[0] != '?' ) ) {
					$param = '?' . $param;
				}

				$poArray[] = $param;
			}
		}

		return $poArray;
	}

	/**
	 * Keeps track of the various Url Arguments used
	 *
	 * @var array of strings in the urlparamater=>value format
	 */
	protected $urlArgs = array();

	/**
	 * Given an array of urlparameter=>value pairs, this method adds them to its
	 * set of Url-arguments. If the urlparameter already exists, it is replaced by the supplied value
	 *
	 * @param array $args
	 */
	protected function setUrlArgs( array $args ) {
		$this->urlArgs = array_merge( $this->urlArgs, $args );
	}

	/**
	 *
	 * @return array of strings in the urlparamater=>value format
	 */
	protected function getUrlArgs() {
		return $this->urlArgs;
	}

	protected function resetUrlArgs() {
		$this->urlArgs = array();
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
		global $smwgResultFormats;

		SMWOutputs::requireResource( 'jquery' );

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

		// @todo FIXME: i18n: Hard coded parentheses.
		$result[0] = "\n" .	'<select id="formatSelector" name="p[format]" onChange="JavaScript:updateOtherOptions(\'' . $url . '\')">' . "\n" .
			'<option value="' . $defFormat . '">' . $printer->getName() .
			' (' . wfMessage( 'smw_ask_defaultformat' )->text() . ')</option>' . "\n";

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
		$result[] .= '<div id="other_options"> ' . $this->showFormatOptions( $params['format'], $params ) . ' </div>';

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

		SMWOutputs::requireScript( 'smwUpdateOptionsQueryUI', $javascript );
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
	 * Required by getFormatSelectBox() to recieve form elements from the
	 * Web. UIs may need to overload processFormatOptions(),
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
