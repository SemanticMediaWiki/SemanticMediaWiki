<?php

use ParamProcessor\Param;
use SMW\Query\PrintRequest;
use SMW\Query\QueryLinker;

/**
 * This special page for MediaWiki implements a customisable form for
 * executing queries outside of articles.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Yaron Koren
 * @author Sanyam Goyal
 * @author Jeroen De Dauw
 * @author mwjames
 *
 * TODO: Split up the megamoths into sane methods.
 */
class SMWAskPage extends SMWQuerySpecialPage {

	private $m_querystring = '';
	private $m_params = array();
	private $m_printouts = array();
	private $m_editquery = false;

	/**
	 * @var Param[]
	 */
	private $params = array();

	public function __construct() {
		parent::__construct( 'Ask' );
	}

	/**
	 * Main entrypoint for the special page.
	 *
	 * @param string $p
	 */
	public function execute( $p ) {
		global $wgOut, $wgRequest, $smwgQEnabled;

		$wgOut->addModules( 'ext.smw.style' );
		$wgOut->addModules( 'ext.smw.ask' );
		$wgOut->addModules( 'ext.smw.property' );

		$this->setHeaders();

		if ( !$smwgQEnabled ) {
			$wgOut->addHTML( '<br />' . wfMessage( 'smw_iq_disabled' )->escaped() );
		} else {
			if ( $wgRequest->getCheck( 'showformatoptions' ) ) {
				// handle Ajax action
				$format = $wgRequest->getVal( 'showformatoptions' );
				$params = $wgRequest->getArray( 'params' );
				$wgOut->disable();
				echo $this->showFormatOptions( $format, $params );
			} else {
				$this->extractQueryParameters( $p );
				$this->makeHTMLResult();
			}
		}

		$this->addExternalHelpLinkFor( 'smw_ask_doculink' );

		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
	}

	/**
	 * This code rather hacky since there are many ways to call that special page, the most involved of
	 * which is the way that this page calls itself when data is submitted via the form (since the shape
	 * of the parameters then is governed by the UI structure, as opposed to being governed by reason).
	 *
	 * TODO: most of this can probably be killed now we are using Validator
	 *
	 * @param string $p
	 */
	protected function extractQueryParameters( $p ) {
		global $wgRequest, $smwgQMaxInlineLimit;

		// First make all inputs into a simple parameter list that can again be parsed into components later.
		if ( $wgRequest->getCheck( 'q' ) ) { // called by own Special, ignore full param string in that case
			$query_val = $wgRequest->getVal( 'p' );

			if ( !empty( $query_val ) ) {
				// p is used for any additional parameters in certain links.
				$rawparams = SMWInfolink::decodeParameters( $query_val, false );
			}
			else {
				$query_values = $wgRequest->getArray( 'p' );

				if ( is_array( $query_values ) ) {
					foreach ( $query_values as $key => $val ) {
						if ( empty( $val ) ) {
							unset( $query_values[$key] );
						}
					}
				}

				// p is used for any additional parameters in certain links.
				$rawparams = SMWInfolink::decodeParameters( $query_values, false );

			}
		} else { // called from wiki, get all parameters
			$rawparams = SMWInfolink::decodeParameters( $p, true );
		}

		// Check for q= query string, used whenever this special page calls itself (via submit or plain link):
		$this->m_querystring = $wgRequest->getText( 'q' );
		if ( $this->m_querystring !== '' ) {
			$rawparams[] = $this->m_querystring;
		}

		// Check for param strings in po (printouts), appears in some links and in submits:
		$paramstring = $wgRequest->getText( 'po' );

		if ( $paramstring !== '' ) { // parameters from HTML input fields
			$ps = explode( "\n", $paramstring ); // params separated by newlines here (compatible with text-input for printouts)

			foreach ( $ps as $param ) { // add initial ? if omitted (all params considered as printouts)
				$param = trim( $param );

				if ( ( $param !== '' ) && ( $param { 0 } != '?' ) ) {
					$param = '?' . $param;
				}

				$rawparams[] = $param;
			}
		}

		// ?Has property=Foo|+index=1
		foreach ( $rawparams as $key => $value ) {
			if (
				( $key !== '' && $key{0} == '?' && strpos( $value, '|' ) !== false ) ||
				( is_string( $value ) && $value !== '' && $value{0} == '?' && strpos( $value, '|' ) !== false ) ) {
				$extra = explode( '|', $value );

				unset( $rawparams[$key] );
				foreach ( $extra as $k => $val) {
					$rawparams[] = $k == 0 && $key{0} == '?' ? $key . '=' . $val : $val;
				}
			}
		}

		// Now parse parameters and rebuilt the param strings for URLs.
		list( $this->m_querystring, $this->m_params, $this->m_printouts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $rawparams, false );

		// Try to complete undefined parameter values from dedicated URL params.
		if ( !array_key_exists( 'format', $this->m_params ) ) {
			$this->m_params['format'] = 'broadtable';
		}

		if ( !array_key_exists( 'order', $this->m_params ) ) {
			$order_values = $wgRequest->getArray( 'order' );

			if ( is_array( $order_values ) ) {
				$this->m_params['order'] = '';

				foreach ( $order_values as $order_value ) {
					if ( $order_value === '' ) {
						$order_value = 'ASC';
					}
					$this->m_params['order'] .= ( $this->m_params['order'] !== '' ? ',' : '' ) . $order_value;
				}
			}
		}

		$this->m_num_sort_values = 0;

		if  ( !array_key_exists( 'sort', $this->m_params ) ) {
			$sort_values = $wgRequest->getArray( 'sort' );
			if ( is_array( $sort_values ) ) {
				$this->m_params['sort'] = implode( ',', $sort_values );
				$this->m_num_sort_values = count( $sort_values );
			}
		}

		if ( !array_key_exists( 'offset', $this->m_params ) ) {
			$this->m_params['offset'] = $wgRequest->getVal( 'offset' );
			if ( $this->m_params['offset'] === '' )  {
				$this->m_params['offset'] = 0;
			}
		}

		if ( !array_key_exists( 'limit', $this->m_params ) ) {
			$this->m_params['limit'] = $wgRequest->getVal( 'limit' );

			if ( $this->m_params['limit'] === '' ) {
				 $this->m_params['limit'] = ( $this->m_params['format'] == 'rss' ) ? 10 : 20; // Standard limit for RSS.
			}
		}

		$this->m_params['limit'] = min( $this->m_params['limit'], $smwgQMaxInlineLimit );

		$this->m_editquery = ( $wgRequest->getVal( 'eq' ) == 'yes' ) || ( $this->m_querystring === '' );
	}

	private function getStoreFromParams( array $params ) {

		$storeId = null;
		$source  = $params['source']->getValue();

		if ( $source !== '' ) {
			$storeId = $GLOBALS['smwgQuerySources'][$source];
		}

		return \SMW\StoreFactory::getStore( $storeId );
	}

	/**
	 * TODO: document
	 */
	protected function makeHTMLResult() {
		global $wgOut;

		// TODO: hold into account $smwgAutocompleteInSpecialAsk

		$result = '';
		$res = null;

		// build parameter strings for URLs, based on current settings
		$urlArgs['q'] = $this->m_querystring;

		$tmp_parray = array();
		foreach ( $this->m_params as $key => $value ) {
			if ( !in_array( $key, array( 'sort', 'order', 'limit', 'offset', 'title' ) ) ) {
				$tmp_parray[$key] = $value;
			}
		}

		$urlArgs['p'] = SMWInfolink::encodeParameters( $tmp_parray );
		$printoutstring = '';
		$duration = 0;
		$navigation = '';

		/**
		 * @var PrintRequest $printout
		 */
		foreach ( $this->m_printouts as $printout ) {
			$printoutstring .= $printout->getSerialisation( true ) . "\n";
		}

		if ( $printoutstring !== '' ) {
			$urlArgs['po'] = $printoutstring;
		}

		if ( array_key_exists( 'sort', $this->m_params ) ) {
			$urlArgs['sort'] = $this->m_params['sort'];
		}

		if ( array_key_exists( 'order', $this->m_params ) ) {
			$urlArgs['order'] = $this->m_params['order'];
		}

		if ( $this->m_querystring !== '' ) {
			// FIXME: this is a hack
			SMWQueryProcessor::addThisPrintout( $this->m_printouts, $this->m_params );
			$params = SMWQueryProcessor::getProcessedParams( $this->m_params, $this->m_printouts );
			$this->m_params['format'] = $params['format']->getValue();

			$this->params = $params;

			$queryobj = SMWQueryProcessor::createQuery(
				$this->m_querystring,
				$params,
				SMWQueryProcessor::SPECIAL_PAGE,
				$this->m_params['format'],
				$this->m_printouts
			);

			/**
			 * @var SMWQueryResult $res
			 */

			// Determine query results
			$duration = microtime( true );
			$res = $this->getStoreFromParams( $params )->getQueryResult( $queryobj );
			$duration = number_format( (microtime( true ) - $duration), 4, '.', '' );

			// Try to be smart for rss/ical if no description/title is given and we have a concept query:
			if ( $this->m_params['format'] == 'rss' ) {
				$desckey = 'rssdescription';
				$titlekey = 'rsstitle';
			} elseif ( $this->m_params['format'] == 'icalendar' ) {
				$desckey = 'icalendardescription';
				$titlekey = 'icalendartitle';
			} else { $desckey = false;
			}

			if ( ( $desckey ) && ( $queryobj->getDescription() instanceof SMWConceptDescription ) &&
			     ( !isset( $this->m_params[$desckey] ) || !isset( $this->m_params[$titlekey] ) ) ) {
				$concept = $queryobj->getDescription()->getConcept();

				if ( !isset( $this->m_params[$titlekey] ) ) {
					$this->m_params[$titlekey] = $concept->getText();
				}

				if ( !isset( $this->m_params[$desckey] ) ) {
					// / @bug The current SMWStore will never return SMWConceptValue (an SMWDataValue) here; it might return SMWDIConcept (an SMWDataItem)
					$dv = end( \SMW\StoreFactory::getStore()->getPropertyValues( SMWWikiPageValue::makePageFromTitle( $concept ), new SMW\DIProperty( '_CONC' ) ) );
					if ( $dv instanceof SMWConceptValue ) {
						$this->m_params[$desckey] = $dv->getDocu();
					}
				}
			}

			$printer = SMWQueryProcessor::getResultPrinter( $this->m_params['format'], SMWQueryProcessor::SPECIAL_PAGE );

			global $wgRequest;

			$hidequery = $wgRequest->getVal( 'eq' ) == 'no';

			if ( !$printer->isExportFormat() ) {
				if ( $res->getCount() > 0 ) {
					if ( $this->m_editquery ) {
						$urlArgs['eq'] = 'yes';
					}
					elseif ( $hidequery ) {
						$urlArgs['eq'] = 'no';
					}

					$navigation = $this->getNavigationBar( $res, $urlArgs );
					$query_result = $printer->getResult( $res, $params, SMW_OUTPUT_HTML );

					if ( is_array( $query_result ) ) {
						$result .= $query_result[0];
					} else {
						$result .= $query_result;
					}

				} else {
					$result = '<div style="text-align: center;">' . wfMessage( 'smw_result_noresults' )->escaped() . '</div>';
				}
			}
		}

		// FileExport will override the header and cause issues during the unit
		// test when fetching the output stream therefore use the plain output
		if ( defined( 'MW_PHPUNIT_TEST' ) && isset( $printer ) && $printer->isExportFormat() ) {
			$result = $printer->getResult( $res, $params, SMW_OUTPUT_FILE );
			$printer = null;
		}

		if ( isset( $printer ) && $printer->isExportFormat() ) {
			$wgOut->disable();

			/**
			 * @var SMWIExportPrinter $printer
			 */
			$printer->outputAsFile( $res, $params );
		} else {
			if ( $this->m_querystring ) {
				$this->getOutput()->setHTMLtitle( $this->m_querystring );
			} else {
				$this->getOutput()->setHTMLtitle( wfMessage( 'ask' )->text() );
			}

			$urlArgs['offset'] = $this->m_params['offset'];
			$urlArgs['limit'] = $this->m_params['limit'];

			$isFromCache = $res !== null ? $res->isFromCache() : false;

			$result = $this->getInputForm(
				$printoutstring,
				wfArrayToCGI( $urlArgs ),
				$navigation,
				$duration,
				$isFromCache
			) . $result;

			$this->getOutput()->addHTML( $result );
		}
	}

	/**
	 * Generates the Search Box UI
	 *
	 * @param string $printoutstring
	 * @param string $urltail
	 *
	 * @return string
	 */
	protected function getInputForm( $printoutstring, $urltail, $navigation = '', $duration, $isFromCache = false ) {
		global $wgScript;

		$result = '';

		// Deprecated: Use of SpecialPage::getTitle was deprecated in MediaWiki 1.23
		$title = method_exists( $this, 'getPageTitle') ? $this->getPageTitle() : $this->getTitle();

		$storeName = get_class( \SMW\StoreFactory::getStore() );

		if ( strpos( $storeName, "\\") !== false ) {
			$storeName = explode("\\", $storeName );
			$storeName = end( $storeName );
		}

		$environment = isset( $this->m_params['source'] ) ? $this->m_params['source'] : $storeName;
		$downloadLink = $this->getExtraDownloadLinks();
		$sarchInfoText = $duration > 0 ? wfMessage( 'smw-ask-query-search-info', $this->m_querystring, $environment, $isFromCache, $duration )->parse() : '';

		$result .= Html::openElement( 'form',
			array( 'action' => $wgScript, 'name' => 'ask', 'method' => 'get' ) );

		if ( $this->m_editquery ) {
			$result .= Html::hidden( 'title', $title->getPrefixedDBKey() );

			// Table for main query and printouts.
			$result .= '<table class="smw-ask-query" style="width: 100%;"><tr><th>' . wfMessage( 'smw_ask_queryhead' )->escaped() . "</th>\n<th>" . wfMessage( 'smw_ask_printhead' )->escaped() . "<br />\n" .
				'<span style="font-weight: normal;">' . wfMessage( 'smw_ask_printdesc' )->escaped() . '</span>' . "</th></tr>\n" .
				'<tr><td style="padding-left: 0px; width: 50%;"><textarea class="smw-ask-query-condition" name="q" cols="20" rows="6">' . htmlspecialchars( $this->m_querystring ) . "</textarea></td>\n" .
				'<td style="padding-left: 7px; width: 50%;"><textarea id="smw-property-input" class="smw-ask-query-printout" name="po" cols="20" rows="6">' . htmlspecialchars( $printoutstring ) . '</textarea></td></tr></table>' . "\n";

			// Format selection
			$result .= self::getFormatSelection ( $this->m_params );

			// @TODO
			// Sorting inputs
			if ( $GLOBALS['smwgQSortingSupport'] ) {
				$result .= '<fieldset class="smw-ask-sorting"><legend>' . wfMessage( 'smw-ask-sorting' )->escaped() . "</legend>\n";
				$result .= self::getSortingOption( $this->m_params );
				$result .= "</fieldset>\n";
			}

			// Other options fieldset
			$result .= '<fieldset class="smw-ask-options smwExpandedFieldset"><legend>' . wfMessage( 'smw_ask_otheroptions' )->escaped() . "</legend>\n";

			// Info text for when the fieldset is collapsed
			$result .= Html::element( 'div', array(
				'class' => 'collapsed-info',
				'style' => 'display:none;'
				), wfMessage( 'smw-ask-otheroptions-collapsed-info')->text()
			);

			// Individual options
			$result .= "<div id=\"other_options\">" . $this->showFormatOptions( $this->m_params['format'], $this->m_params ) . "</div>";
			$result .= "</fieldset>\n";

			$urltail = str_replace( '&eq=yes', '', $urltail ) . '&eq=no'; // FIXME: doing it wrong, srysly
			$btnFindResults = '<input type="submit" class="smw-ask-action-btn smw-ask-action-btn-dblue" value="' . wfMessage( 'smw_ask_submit' )->escaped() . '"/>' . ' ' .
				'<input type="hidden" name="eq" value="yes"/>' . ' ';
			$msgShowHide = 'smw_ask_hidequery';

		} else { // if $this->m_editquery == false
			$urltail = str_replace( '&eq=no', '', $urltail ) . '&eq=yes';
			$btnFindResults = '';
			$msgShowHide = 'smw_ask_editquery';
		}

		// Submit
		$result .= '<fieldset class="smw-ask-actions" style="margin-top:0px;"><legend>' . wfMessage( 'smw-ask-search' )->escaped() . "</legend>\n" .
			'<p>' .  '' . '</p>' .

			$btnFindResults .

			Html::element(
				'a',
				array(
					'class' => 'smw-ask-action-btn smw-ask-action-btn-lblue',
					'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( $urltail ),
					'rel' => 'nofollow'
				), wfMessage( $msgShowHide )->text()
			) .
			' ' . self::getEmbedToggle();

		//show|hide inline embed code
		$result .= '<div id="inlinequeryembed" style="display: none"><div id="inlinequeryembedinstruct">' . wfMessage( 'smw_ask_embed_instr' )->escaped() . '</div><textarea id="inlinequeryembedarea" readonly="yes" cols="20" rows="6" onclick="this.select()">' .
			'{{#ask:' . htmlspecialchars( $this->m_querystring ) . "\n";

		foreach ( $this->m_printouts as $printout ) {
			$serialization = $printout->getSerialisation( true );
			$mainlabel = isset( $this->m_params['mainlabel'] ) ? '?=' . $this->m_params['mainlabel'] . '#' : '';

			if ( $serialization !== '?#' && $serialization !== $mainlabel ) {
				$result .= ' |' . $serialization . "\n";
			}
		}

		foreach ( $this->params as $param ) {
			if ( !$param->wasSetToDefault() ) {
				$result .= ' |' . htmlspecialchars( $param->getName() ) . '=';
				$result .= htmlspecialchars( $this->m_params[$param->getName()] ) . "\n";
			}
		}

		$result .= '}}</textarea></div><p></p>';
		$result .= ( $navigation !== '' ? '<p>'. $sarchInfoText . '</p>' . '<hr class="smw-form-horizontalrule">' .  $navigation . '&#160;&#160;&#160;' . $downloadLink : '' ) .
			"\n</fieldset>\n</form>\n";

		return $result;
	}

	/**
	 * Build the format drop down
	 *
	 * @param array
	 *
	 * @return string
	 */
	protected static function getFormatSelection ( $params ) {
		$result = '';

		$printer = SMWQueryProcessor::getResultPrinter( 'broadtable', SMWQueryProcessor::SPECIAL_PAGE );
		$url = SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( 'showformatoptions=this.value' );

		foreach ( $params as $param => $value ) {
			if ( $param !== 'format' ) {
				$url .= '&params[' . rawurlencode( $param ) . ']=' . rawurlencode( $value );
			}
		}

		$result .= '<br /><span class="smw-ask-query-format" style="vertical-align:middle;">' . wfMessage( 'smw_ask_format_as' )->escaped() . ' <input type="hidden" name="eq" value="yes"/>' . "\n" .
			Html::openElement(
				'select',
				array(
					'class' => 'smw-ask-query-format-selector',
					'id' => 'formatSelector',
					'name' => 'p[format]',
					'data-url' => $url,
				)
			) . "\n" .
			'	<option value="broadtable"' . ( $params['format'] == 'broadtable' ? ' selected="selected"' : '' ) . '>' .
			htmlspecialchars( $printer->getName() ) . ' (' . wfMessage( 'smw_ask_defaultformat' )->escaped() . ')</option>' . "\n";

		$formats = array();

		foreach ( array_keys( $GLOBALS['smwgResultFormats'] ) as $format ) {
			// Special formats "count" and "debug" currently not supported.
			if ( $format != 'broadtable' && $format != 'count' && $format != 'debug' ) {
				$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );
				$formats[$format] = htmlspecialchars( $printer->getName() );
			}
		}

		natcasesort( $formats );

		foreach ( $formats as $format => $name ) {
			$result .= '	<option value="' . $format . '"' . ( $params['format'] == $format ? ' selected="selected"' : '' ) . '>' . $name . "</option>\n";
		}

		$result .= "</select></span>\n";

		return $result;
	}

	/**
	 * Build the sorting/order input
	 *
	 * @param array
	 *
	 * @return string
	 */
	protected static function getSortingOption ( $params ) {
		$result = '';

		if ( ! array_key_exists( 'sort', $params ) || ! array_key_exists( 'order', $params ) ) {
			$orders = array(); // do not even show one sort input here
		} else {
			$sorts = explode( ',', $params['sort'] );
			$orders = explode( ',', $params['order'] );
			reset( $sorts );
		}

		foreach ( $orders as $i => $order ) {
			$result .=  "<div id=\"sort_div_$i\">" . wfMessage( 'smw_ask_sortby' )->escaped() . ' <input type="text" name="sort[' . $i . ']" value="' .
				    htmlspecialchars( $sorts[$i] ) . "\" size=\"35\"/>\n" . '<select name="order[' . $i . ']"><option ';

			if ( $order == 'ASC' ) {
				$result .= 'selected="selected" ';
			}
			$result .=  'value="ASC">' . wfMessage( 'smw_ask_ascorder' )->escaped() . '</option><option ';
			if ( $order == 'DESC' ) {
				$result .= 'selected="selected" ';
			}

			$result .=  'value="DESC">' . wfMessage( 'smw_ask_descorder' )->escaped() . "</option></select>\n";
			$result .= '[<a class="smw-ask-delete" data-target="sort_div_' . $i . '" href="#">' . wfMessage( 'delete' )->escaped() . '</a>]' . "\n";
			$result .= "</div>\n";
		}

		$result .=  '<div id="sorting_starter" style="display: none">' . wfMessage( 'smw_ask_sortby' )->escaped() . ' <input type="text" name="sort_num" size="35" class="smw-property-input" />' . "\n";
		$result .= ' <select name="order_num">' . "\n";
		$result .= '	<option value="ASC">' . wfMessage( 'smw_ask_ascorder' )->escaped() . "</option>\n";
		$result .= '	<option value="DESC">' . wfMessage( 'smw_ask_descorder' )->escaped() . "</option>\n</select>\n";
		$result .= "</div>\n";
		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '<a class="smw-ask-add" href="#">' . wfMessage( 'smw_add_sortcondition' )->escaped() . '</a>' . "\n";

		return $result;
	}

	/**
	 * TODO: document
	 *
	 * @return string
	 */
	protected static function getEmbedToggle() {
		return '<span id="embed_show"><a href="#embed_show" class="smw-ask-action-btn smw-ask-action-btn-lblue" rel="nofollow" onclick="' .
			"document.getElementById('inlinequeryembed').style.display='block';" .
			"document.getElementById('embed_hide').style.display='inline';" .
			"document.getElementById('embed_show').style.display='none';" .
			"document.getElementById('inlinequeryembedarea').select();" .
			'">' . wfMessage( 'smw_ask_show_embed' )->escaped() . '</a></span>' .
			'<span id="embed_hide" style="display: none"><a href="#embed_hide" class="smw-ask-action-btn smw-ask-action-btn-lblue" rel="nofollow" onclick="' .
			"document.getElementById('inlinequeryembed').style.display='none';" .
			"document.getElementById('embed_show').style.display='inline';" .
			"document.getElementById('embed_hide').style.display='none';" .
			'">' . wfMessage( 'smw_ask_hide_embed' )->escaped() . '</a></span>';
	}

	/**
	 * Build the navigation for some given query result, reuse url-tail parameters.
	 *
	 * @param SMWQueryResult $res
	 * @param array $urlArgs
	 *
	 * @return string
	 */
	protected function getNavigationBar( SMWQueryResult $res, array $urlArgs ) {
		global $smwgQMaxInlineLimit, $wgLang;

		// Bug 49216
		$offset = $res->getQuery()->getOffset();
		$limit  = $this->params['limit']->getValue();
		$navigation = '';

		// @todo FIXME: i18n: Patchwork text.
		$navigation .=
			'<b>' .
				wfMessage( 'smw_result_results' )->escaped() . ' ' . $wgLang->formatNum( $offset + 1 ) .
			' &#150; ' .
				$wgLang->formatNum( $offset + $res->getCount() ) .
			'</b>&#160;&#160;&#160;&#160;';

		// Prepare navigation bar.
		if ( $offset > 0 ) {
			$navigation .= '(' . Html::element(
				'a',
				array(
					'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( array(
						'offset' => max( 0, $offset - $limit ),
						'limit' => $limit
					) + $urlArgs ),
					'rel' => 'nofollow'
				),
				wfMessage( 'smw_result_prev' )->text() . ' ' . $limit
			) . ' | ';
		} else {
			$navigation .= '(' . wfMessage( 'smw_result_prev' )->escaped() . ' ' . $limit . ' | ';
		}

		if ( $res->hasFurtherResults() ) {
			$navigation .= Html::element(
				'a',
				array(
					'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( array(
						'offset' => ( $offset + $limit ),
						'limit' => $limit
					)  + $urlArgs ),
					'rel' => 'nofollow'
				),
				wfMessage( 'smw_result_next' )->text() . ' ' . $limit
			) . ')';
		} else {
			$navigation .= wfMessage( 'smw_result_next' )->escaped() . ' ' . $limit . ')';
		}

		$first = true;

		foreach ( array( 20, 50, 100, 250, 500 ) as $l ) {
			if ( $l > $smwgQMaxInlineLimit ) {
				break;
			}

			if ( $first ) {
				$navigation .= '&#160;&#160;&#160;(';
				$first = false;
			} else {
				$navigation .= ' | ';
			}

			if ( $limit != $l ) {
				$navigation .= Html::element(
					'a',
					array(
						'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( array(
							'offset' => $offset,
							'limit' => $l
						) + $urlArgs ),
						'rel' => 'nofollow'
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

	protected function getGroupName() {
		return 'smw_group';
	}

	private function getExtraDownloadLinks() {

		$downloadLinks = '';

		if ( $this->m_querystring === '' ) {
			return $downloadLinks;
		}

		$params = $this->m_params;
		$params = SMWQueryProcessor::getProcessedParams( $params, $this->m_printouts );

		$query = SMWQueryProcessor::createQuery(
			$this->m_querystring,
			$params,
			SMWQueryProcessor::SPECIAL_PAGE,
			'',
			$this->m_printouts
		);

		$link = QueryLinker::get( $query );
		$link->setParameter( 'true', 'prettyprint' );
		$link->setParameter( 'json', 'format' );
		$link->setCaption( 'JSON' );

		$downloadLinks .= $link->getHtml();

		$link = QueryLinker::get( $query );
		$link->setCaption( 'CSV' );
		$link->setParameter( 'csv', 'format' );

		$downloadLinks .= ' | ' . $link->getHtml();

		$link = QueryLinker::get( $query );
		$link->setCaption( 'RSS' );
		$link->setParameter( 'rss', 'format' );

		$downloadLinks .= ' | ' . $link->getHtml();

		$link = QueryLinker::get( $query );
		$link->setCaption( 'RDF' );
		$link->setParameter( 'rdf', 'format' );

		$downloadLinks .= ' | ' . $link->getHtml();

		return '(' . $downloadLinks . ')';
	}

	/**
	 * FIXME MW 1.25
	 */
	private function addExternalHelpLinkFor( $key ) {

		if ( !method_exists( $this, 'addHelpLink' ) ) {
			return null;
		}

		$this->addHelpLink( wfMessage( $key )->escaped(), true );
	}

}
