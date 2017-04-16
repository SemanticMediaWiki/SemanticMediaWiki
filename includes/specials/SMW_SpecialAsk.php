<?php

use ParamProcessor\Param;
use SMW\Query\PrintRequest;
use SMW\Query\QueryLinker;
use SMW\MediaWiki\Specials\Ask\ErrorFormWidget;
use SMW\MediaWiki\Specials\Ask\InputFormWidget;
use SMW\MediaWiki\Specials\Ask\ParametersFormWidget;
use SMW\ApplicationFactory;

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
class SMWAskPage extends SpecialPage {

	private $m_querystring = '';
	private $m_params = array();
	private $m_printouts = array();
	private $m_editquery = false;
	private $queryLinker = null;

	/**
	 * @var InputFormWidget
	 */
	private $inputFormWidget;

	/**
	 * @var ErrorFormWidget
	 */
	private $errorFormWidget;

	/**
	 * @var ParametersFormWidget
	 */
	private $parametersFormWidget;

	/**
	 * @var Param[]
	 */
	private $params = array();

	public function __construct() {
		parent::__construct( 'Ask' );

		$this->inputFormWidget = new InputFormWidget();
		$this->errorFormWidget = new ErrorFormWidget();
		$this->parametersFormWidget = new ParametersFormWidget();
	}

	/**
	 * SpecialPage::doesWrites
	 *
	 * @return boolean
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * Main entrypoint for the special page.
	 *
	 * @param string $p
	 */
	public function execute( $p ) {
		global $smwgQEnabled;

		$out = $this->getOutput();
		$request = $this->getRequest();

		$this->parametersFormWidget->setTooltipDisplay(
			$this->getUser()->getOption( 'smw-prefs-ask-options-tooltip-display' )
		);

		$out->addModules( 'ext.smw.style' );
		$out->addModules( 'ext.smw.ask' );
		$out->addModules( 'ext.smw.property' );

		$this->setHeaders();

		if ( !$smwgQEnabled ) {
			$out->addHTML( '<br />' . wfMessage( 'smw_iq_disabled' )->escaped() );
		} else {
			if ( $request->getCheck( 'showformatoptions' ) ) {
				// handle Ajax action
				$format = $request->getVal( 'showformatoptions' );
				$params = $request->getArray( 'params' );
				$out->disable();
				echo $this->parametersFormWidget->createParametersForm( $format, $params );
			} else {
				$this->extractQueryParameters( $p );
				$this->makeHTMLResult();
			}
		}

		$this->addExternalHelpLinkFor( 'smw_ask_doculink' );

		SMWOutputs::commitToOutputPage( $out ); // make sure locally collected output data is pushed to the output!
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
		global $smwgQMaxInlineLimit;

		$request = $this->getRequest();

		// First make all inputs into a simple parameter list that can again be parsed into components later.
		if ( $request->getCheck( 'q' ) ) { // called by own Special, ignore full param string in that case
			$query_val = $request->getVal( 'p' );

			if ( !empty( $query_val ) ) {
				// p is used for any additional parameters in certain links.
				$rawparams = SMWInfolink::decodeParameters( $query_val, false );
			}
			else {
				$query_values = $request->getArray( 'p' );

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
		$this->m_querystring = $request->getText( 'q' );
		if ( $this->m_querystring !== '' ) {
			$rawparams[] = $this->m_querystring;
		}

		// Check for param strings in po (printouts), appears in some links and in submits:
		$paramstring = $request->getText( 'po' );

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

		list( $this->m_querystring, $this->m_params, $this->m_printouts ) = $this->getComponentsFromParameters(
			$rawparams
		);

		// Try to complete undefined parameter values from dedicated URL params.
		if ( !array_key_exists( 'format', $this->m_params ) ) {
			$this->m_params['format'] = 'broadtable';
		}

		if ( !array_key_exists( 'order', $this->m_params ) ) {
			$order_values = $request->getArray( 'order' );

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
			$sort_values = $request->getArray( 'sort' );
			if ( is_array( $sort_values ) ) {
				$this->m_params['sort'] = implode( ',', $sort_values );
				$this->m_num_sort_values = count( $sort_values );
			}
		}

		if ( !array_key_exists( 'offset', $this->m_params ) ) {
			$this->m_params['offset'] = $request->getVal( 'offset' );
			if ( $this->m_params['offset'] === '' )  {
				$this->m_params['offset'] = 0;
			}
		}

		if ( !array_key_exists( 'limit', $this->m_params ) ) {
			$this->m_params['limit'] = $request->getVal( 'limit' );

			if ( $this->m_params['limit'] === '' ) {
				 $this->m_params['limit'] = ( $this->m_params['format'] == 'rss' ) ? 10 : 20; // Standard limit for RSS.
			}
		}

		$this->m_params['limit'] = min( $this->m_params['limit'], $smwgQMaxInlineLimit );

		$this->m_editquery = ( $request->getVal( 'eq' ) == 'yes' ) || ( $this->m_querystring === '' );
	}

	private function getStoreFromParams( array $params ) {
		return ApplicationFactory::getInstance()->getQuerySourceFactory()->get( $params['source']->getValue() );
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
		$queryobj = null;

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

		if ( $this->getRequest()->getCheck( 'bTitle' ) ) {
			$urlArgs['bTitle'] = $this->getRequest()->getVal( 'bTitle' );
			$urlArgs['bMsg'] = $this->getRequest()->getVal( 'bMsg' );
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

			$queryobj->setOption( SMWQuery::PROC_CONTEXT, 'SpecialAsk' );
			$this->queryLinker = QueryLinker::get( $queryobj, $this->m_params );

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
			$printer->setShowErrors( false );

			global $wgRequest;

			$hidequery = $wgRequest->getVal( 'eq' ) == 'no';
			$debug = '';

			// Allow to generate a debug output
			if ( $this->getRequest()->getVal( 'debug' ) ) {

				$queryobj = SMWQueryProcessor::createQuery(
					$this->m_querystring,
					$params,
					SMWQueryProcessor::SPECIAL_PAGE,
					'debug',
					$this->m_printouts
				);

				$debug = $this->getStoreFromParams( $params )->getQueryResult( $queryobj );
			}

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

					$result .= $debug;
				} else {
					$result = $this->errorFormWidget->createNoResultFormElement();
					$result .= $debug;
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
			) . $this->errorFormWidget->getFormattedQueryErrorElement( $queryobj ) . $result;

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

		$querySource = ApplicationFactory::getInstance()->getQuerySourceFactory()->getAsString(
			isset( $this->m_params['source'] ) ? $this->m_params['source'] : null
		);

		$downloadLink = $this->getExtraDownloadLinks();
		$searchInfoText = $duration > 0 ? wfMessage( 'smw-ask-query-search-info', $this->m_querystring, $querySource, $isFromCache, $duration )->parse() : '';
		$hideForm = false;

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
			$result .= '<fieldset class="smw-ask-options-fields"><legend>' . wfMessage( 'smw_ask_otheroptions' )->escaped() . "</legend>\n";

			// Info text for when the fieldset is collapsed
			$result .= Html::element( 'div', array(
				'class' => 'collapsed-info',
				'style' => 'display:none;'
				), wfMessage( 'smw-ask-otheroptions-collapsed-info')->text()
			);

			// Individual options
			$result .= "<div id=\"other_options\">" .  $this->parametersFormWidget->createParametersForm( $this->m_params['format'], $this->m_params ) . "</div>";
			$result .= "</fieldset>\n";

			$urltail = str_replace( '&eq=yes', '', $urltail ) . '&eq=no'; // FIXME: doing it wrong, srysly
			$hideForm = true;
		} else { // if $this->m_editquery == false
			$urltail = str_replace( '&eq=no', '', $urltail ) . '&eq=yes';
		}

		// Submit
		$result .= '<fieldset class="smw-ask-actions" style="margin-top:0px;"><legend>' . wfMessage( 'smw-ask-search' )->escaped() . "</legend>\n" .
			'<p>' .  '' . '</p>' .

			$this->inputFormWidget->createFindResultLinkElement( $hideForm ) .
			' ' . $this->inputFormWidget->createShowHideLinkElement( SpecialPage::getSafeTitleFor( 'Ask' ), $urltail, $hideForm ) .
			' ' . $this->inputFormWidget->createEmbeddedCodeLinkElement() .
			' ' . $this->inputFormWidget->createClipboardLinkElement( $this->queryLinker ) .
			' ' . $this->inputFormWidget->createEmbeddedCodeElement( $this->getQueryAsCodeString() );

		$result .= '<p></p>';

		$this->doFinalModificationsOnBorrowedOutput(
			$result,
			$searchInfoText
		);

		$result .= ( $navigation !== '' ? '<p>'. $searchInfoText . '</p>' . '<hr class="smw-form-horizontalrule">' .  $navigation . '&#160;&#160;&#160;' . $downloadLink : '' ) .
			"\n</fieldset>\n</form>\n";


		$this->getOutput()->addModules(
			$this->inputFormWidget->getResourceModules()
		);

		return $result;
	}

	private function getQueryAsCodeString() {

		$code = $this->m_querystring ? htmlspecialchars( $this->m_querystring ) . "\n" : "\n";

		foreach ( $this->m_printouts as $printout ) {
			$serialization = $printout->getSerialisation( true );
			$mainlabel = isset( $this->m_params['mainlabel'] ) ? '?=' . $this->m_params['mainlabel'] . '#' : '';

			if ( $serialization !== '?#' && $serialization !== $mainlabel ) {
				$code .= ' |' . $serialization . "\n";
			}
		}

		foreach ( $this->params as $param ) {
			if ( !$param->wasSetToDefault() ) {
				$code .= ' |' . htmlspecialchars( $param->getName() ) . '=';
				$code .= htmlspecialchars( $this->m_params[$param->getName()] ) . "\n";
			}
		}

		return '{{#ask: ' . $code . '}}';
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

		if ( $this->queryLinker === null ) {
			return $downloadLinks;
		}

		$queryLinker = clone $this->queryLinker;

		$queryLinker->setParameter( 'true', 'prettyprint' );
		$queryLinker->setParameter( 'true', 'unescape' );
		$queryLinker->setParameter( 'json', 'format' );
		$queryLinker->setParameter( 'JSON', 'searchlabel' );
		$queryLinker->setCaption( 'JSON' );

		$downloadLinks .= $queryLinker->getHtml();

		$queryLinker->setCaption( 'CSV' );
		$queryLinker->setParameter( 'csv', 'format' );
		$queryLinker->setParameter( 'CSV', 'searchlabel' );

		$downloadLinks .= ' | ' . $queryLinker->getHtml();

		$queryLinker->setCaption( 'RSS' );
		$queryLinker->setParameter( 'rss', 'format' );
		$queryLinker->setParameter( 'RSS', 'searchlabel' );

		$downloadLinks .= ' | ' . $queryLinker->getHtml();

		$queryLinker->setCaption( 'RDF' );
		$queryLinker->setParameter( 'rdf', 'format' );
		$queryLinker->setParameter( 'RDF', 'searchlabel' );

		$downloadLinks .= ' | ' . $queryLinker->getHtml();

		return '(' . $downloadLinks . ')';
	}

	private function doFinalModificationsOnBorrowedOutput( &$html, &$searchInfoText ) {

		if ( !$this->getRequest()->getCheck( 'bTitle' ) ) {
			return;
		}

		$borrowedMessage = $this->getRequest()->getVal( 'bMsg' );

		$searchInfoText = '';
		$html = "\n<fieldset><p>" . ( $borrowedMessage !== null && wfMessage( $borrowedMessage )->exists() ? wfMessage( $borrowedMessage )->parse() : '' ) . "</p>";

		$borrowedTitle = $this->getRequest()->getVal( 'bTitle' );

		if ( $borrowedTitle !== null && wfMessage( $borrowedTitle )->exists() ) {
			$this->getOutput()->setPageTitle( wfMessage( $borrowedTitle )->text() );
		}
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

	private function getComponentsFromParameters( $reqParameters ) {

		$parameters = array();
		unset( $reqParameters['title'] );

		// Split ?Has property=Foo|+index=1 into a [ '?Has property=Foo', '+index=1' ]
		foreach ( $reqParameters as $key => $value ) {
			if (
				( $key !== '' && $key{0} == '?' && strpos( $value, '|' ) !== false ) ||
				( is_string( $value ) && $value !== '' && $value{0} == '?' && strpos( $value, '|' ) !== false ) ) {

				foreach ( explode( '|', $value ) as $k => $val ) {
					$parameters[] = $k == 0 && $key{0} == '?' ? $key . '=' . $val : $val;
				}
			} elseif ( is_string( $key ) ) {
				$parameters[$key] = $value;
			} else {
				$parameters[] = $value;
			}
		}

		// Now parse parameters and rebuilt the param strings for URLs.
		return SMWQueryProcessor::getComponentsFromFunctionParams( $parameters, false );
	}

}
