<?php

use ParamProcessor\Param;
use SMW\Query\PrintRequest;
use SMW\Query\QueryLinker;
use SMW\MediaWiki\Specials\Ask\ErrorWidget;
use SMW\MediaWiki\Specials\Ask\LinksWidget;
use SMW\MediaWiki\Specials\Ask\ParametersWidget;
use SMW\MediaWiki\Specials\Ask\FormatterWidget;
use SMW\MediaWiki\Specials\Ask\NavigationLinksWidget;
use SMW\MediaWiki\Specials\Ask\DownloadLinksWidget;
use SMW\MediaWiki\Specials\Ask\SortWidget;
use SMW\MediaWiki\Specials\Ask\FormatSelectionWidget;
use SMW\MediaWiki\Specials\Ask\QueryInputWidget;
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
	 * @var Param[]
	 */
	private $params = array();

	public function __construct() {
		parent::__construct( 'Ask' );
	}

	/**
	 * @see SpecialPage::doesWrites
	 *
	 * @return boolean
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string $p
	 */
	public function execute( $p ) {
		global $smwgQEnabled;

		$out = $this->getOutput();
		$request = $this->getRequest();

		$out->addModuleStyles( 'ext.smw.style' );
		$out->addModuleStyles( 'ext.smw.ask.styles' );
		$out->addModuleStyles( 'ext.smw.table.styles' );

		$out->addModules( 'ext.smw.ask' );
		$out->addModules( 'ext.smw.property' );

		$out->addModules(
			LinksWidget::getModules()
		);

		$this->setHeaders();

		$request->setVal( 'wpEditToken',
			$this->getUser()->getEditToken()
		);

		// #2590
		if ( !$this->getUser()->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			return $out->addHtml( ErrorWidget::sessionFailure() );
		}

		$out->addHTML( ErrorWidget::noScript() );

		NavigationLinksWidget::setMaxInlineLimit(
			$GLOBALS['smwgQMaxInlineLimit']
		);

		FormatSelectionWidget::setResultFormats(
			$GLOBALS['smwgResultFormats']
		);

		ParametersWidget::setTooltipDisplay(
			$this->getUser()->getOption( 'smw-prefs-ask-options-tooltip-display' )
		);

		ParametersWidget::setDefaultLimit(
			$GLOBALS['smwgQDefaultLimit']
		);

		SortWidget::setSortingSupport(
			$GLOBALS['smwgQSortingSupport']
		);

		// @see #835
		SortWidget::setRandSortingSupport(
			$GLOBALS['smwgQRandSortingSupport']
		);

		if ( $request->getCheck( 'bTitle' ) ) {
			$visibleLinks = [];
		} elseif( $request->getVal( 'eq' ) === 'no' || $p !== null || $request->getVal( 'x' ) ) {
			$visibleLinks = [ 'empty' ];
		} else {
			$visibleLinks = [ 'options', 'search', 'empty' ];
		}

		$out->addHTML(
			NavigationLinksWidget::topLinks(
				SpecialPage::getSafeTitleFor( 'Ask' ),
				$visibleLinks
			)
		);

		if ( !$smwgQEnabled ) {
			$out->addHTML( '<br />' . wfMessage( 'smw_iq_disabled' )->escaped() );
		} else {
			if ( $request->getCheck( 'showformatoptions' ) ) {
				// handle Ajax action
				$format = $request->getVal( 'showformatoptions' );
				$params = $request->getArray( 'params' );
				$out->disable();
				echo ParametersWidget::parameterList( $format, $params );
			} else {
				$this->extractQueryParameters( $p );
				$this->makeHTMLResult();
			}
		}

		$this->addExternalHelpLinkFor( 'smw_ask_doculink' );

		// make sure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $out );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group';
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

		$sort_count = 0;

		// First check whether the sorting options input send an
		// request data as array
		if ( $request->getArray( 'sort_num', [] ) !== array() ) {
			$sort_values = $request->getArray( 'sort_num' );

			if ( is_array( $sort_values ) ) {
				$sort = array_filter( $sort_values );
				$sort_count = count( $sort );
				$this->m_params['sort'] = implode( ',', $sort );
			}
		} elseif ( $request->getCheck( 'sort' ) ) {
			$this->m_params['sort'] = $request->getVal( 'sort', '' );
		}

		// First check whether the order options input send an
		// request data as array
		if ( $request->getArray( 'order_num', [] ) !== array()  ) {
			$order_values = $request->getArray( 'order_num' );

			// Count doesn't match means we have a order from an
			// empty (#subject) carrying around which we don't permit when
			// sorting via columns
			if ( count( $order_values ) != $sort_count ) {
				array_pop( $order_values );
			}

			if ( is_array( $order_values ) ) {
				$order = array_filter( $order_values );
				$this->m_params['order'] = implode( ',', $order );
			}

		} elseif ( $request->getCheck( 'order' ) ) {
			$this->m_params['order'] = $request->getVal( 'order', '' );
		} elseif ( !array_key_exists( 'offset', $this->m_params ) ) {
			$this->m_params['order'] = 'asc';
			$this->m_params['sort'] = '';
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
			if ( $this->getRequest()->getVal( 'debug' ) && ( !isset( $this->m_params['source'] ) || $this->m_params['source'] === '' ) ) {

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

					$navigation = NavigationLinksWidget::navigationLinks(
						SpecialPage::getSafeTitleFor( 'Ask' ),
						$this->params['limit']->getValue(),
						$res->getQuery()->getOffset(),
						$res->getCount(),
						$res->hasFurtherResults(),
						$urlArgs
					);

					$query_result = $printer->getResult( $res, $params, SMW_OUTPUT_HTML );

					$result .= $debug;

					if ( is_array( $query_result ) ) {
						$result .= $query_result[0];
					} else {
						$result .= $query_result;
					}

				} else {
					$result = ErrorWidget::noResult();
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

			$result = FormatterWidget::div(
				$result,
				[
					'id' => 'result',
					"class" => 'smw-ask-result'
				]
			);

			$result = $this->getInputForm(
				$printoutstring,
				wfArrayToCGI( $urlArgs ),
				$navigation,
				$duration,
				$isFromCache
			) . ErrorWidget::queryError( $queryobj ) . $result;

			// The overall form is "soft-disabled" so that when JS is fully
			// loaded, the ask module will remove this class and releases the form
			// for input
			$this->getOutput()->addHTML(
				FormatterWidget::div(
					$result,
					[
						'id' => 'ask',
						"class" => 'is-disabled'
					]
				)
			);
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

		$downloadLink = DownloadLinksWidget::downloadLinks( $this->queryLinker );
		$searchInfoText = $duration > 0 ? wfMessage( 'smw-ask-query-search-info', $this->m_querystring, $querySource, $isFromCache, $duration )->parse() : '';

		$hideForm = false;
		$title = SpecialPage::getSafeTitleFor( 'Ask' );

		$sorting = SortWidget::sortSection( $this->m_params );

		$result .= Html::openElement( 'form',
			array( 'action' => $wgScript, 'name' => 'ask', 'method' => 'get' ) );

		if ( $this->m_editquery ) {
			$result .= Html::hidden( 'title', $title->getPrefixedDBKey() );

			// Table for main query and printouts.
			$result .= QueryInputWidget::input( $this->m_querystring , $printoutstring );

			// Format selection
			$result .= FormatSelectionWidget::selection( $title, $this->m_params );

			// Other options fieldset
			$result .= '<fieldset id="options" class="smw-ask-options"><legend>' . wfMessage( 'smw-ask-options' )->escaped() . "</legend>\n";

			// Individual options
			$result .= "<div id=\"options-list\">" .  ParametersWidget::parameterList( $this->m_params['format'], $this->m_params ) . "</div>";
						$result .= $sorting;
			$result .= "</fieldset>\n";

			$urltail = str_replace( '&eq=yes', '', $urltail ) . '&eq=no'; // FIXME: doing it wrong, srysly
			$hideForm = true;
		} else { // if $this->m_editquery == false
			$urltail = str_replace( '&eq=no', '', $urltail ) . '&eq=yes';
		}

		$isEmpty = $this->queryLinker === null;

		// Submit
		$result .= '<div id="search" class="smw-ask-search">' . '<fieldset class="smw-ask-actions" style="margin-top:0px;"><legend>' . wfMessage( 'smw-ask-search' )->escaped() . "</legend>\n" .
			'<p>' .  '' . '</p>' .

			LinksWidget::resultSubmitLink( $hideForm ) .
			LinksWidget::showHideLink( $title, $urltail, $hideForm, $isEmpty ) .
			LinksWidget::clipboardLink( $this->queryLinker );

			if ( !isset( $this->m_params['source'] ) || $this->m_params['source'] === '' ) {
				$result .= LinksWidget::debugLink( $title, $urltail, $isEmpty );
			}

			$result .= LinksWidget::embeddedCodeLink( $isEmpty ) .
			LinksWidget::embeddedCodeBlock( $this->getQueryAsCodeString() );

		$result .= '<p></p>';

		$this->doFinalModificationsOnBorrowedOutput(
			$result,
			$searchInfoText
		);

		$result .= ( $navigation !== '' ? '<div class="smw-ask-cond-info">'. $searchInfoText . '</div>' . '<hr class="smw-form-horizontalrule"><div class="smw-ask-actions-nav">' .  $navigation . '&#160;&#160;&#160;' . $downloadLink : '' ) . '</div>' .
			"\n</fieldset></div>\n</form>\n";

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

			if ( !isset( $this->m_params[$param->getName()] ) ) {
				continue;
			}

			if ( !$param->wasSetToDefault() ) {
				$code .= ' |' . htmlspecialchars( $param->getName() ) . '=';
				$code .= htmlspecialchars( $this->m_params[$param->getName()] ) . "\n";
			}
		}

		return '{{#ask: ' . $code . '}}';
	}

	private function doFinalModificationsOnBorrowedOutput( &$html, &$searchInfoText ) {

		if ( !$this->getRequest()->getCheck( 'bTitle' ) ) {
			return;
		}

		$borrowedMessage = $this->getRequest()->getVal( 'bMsg' );

		$searchInfoText = '';
		$html = "\n<fieldset class='smw-ask-actions'><p>" . ( $borrowedMessage !== null && wfMessage( $borrowedMessage )->exists() ? wfMessage( $borrowedMessage )->parse() : '' ) . "</p>";

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
