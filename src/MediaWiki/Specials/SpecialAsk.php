<?php

namespace SMW\MediaWiki\Specials;

use Html;
use ParamProcessor\Param;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Specials\Ask\ErrorWidget;
use SMW\MediaWiki\Specials\Ask\FormatListWidget;
use SMW\MediaWiki\Specials\Ask\HelpWidget;
use SMW\MediaWiki\Specials\Ask\LinksWidget;
use SMW\MediaWiki\Specials\Ask\NavigationLinksWidget;
use SMW\MediaWiki\Specials\Ask\ParametersProcessor;
use SMW\MediaWiki\Specials\Ask\ParametersWidget;
use SMW\MediaWiki\Specials\Ask\QueryInputWidget;
use SMW\MediaWiki\Specials\Ask\SortWidget;
use SMW\MediaWiki\Specials\Ask\UrlArgs;
use SMW\MediaWiki\Specials\Ask\HtmlForm;
use SMW\Query\PrintRequest;
use SMW\Query\QueryLinker;
use SMW\Query\RemoteRequest;
use SMW\Query\Result\StringResult;
use SMW\Utils\HtmlModal;
use SMWInfolink as Infolink;
use SMWOutputs;
use SMWQuery;
use SMWQueryProcessor as QueryProcessor;
use SMWQueryResult as QueryResult;
use SpecialPage;
use SMW\Utils\HtmlTabs;
use SMW\Message;

/**
 * This special page for MediaWiki implements a customisable form for executing
 * queries outside of articles.
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 * @author Markus Krötzsch
 * @author Yaron Koren
 * @author Sanyam Goyal
 * @author Jeroen De Dauw
 */
class SpecialAsk extends SpecialPage {

	/**
	 * @var QuerySourceFactory
	 */
	private $querySourceFactory;

	/**
	 * @var string
	 */
	private $queryString = '';

	/**
	 * @var array
	 */
	private $parameters = [];

	/**
	 * @var array
	 */
	private $printouts = [];

	/**
	 * @var boolean
	 */
	private $isEditMode = false;

	/**
	 * @var boolean
	 */
	private $isBorrowedMode = false;

	/**
	 * @var Param[]
	 */
	private $params = [];

	public function __construct() {
		parent::__construct( 'Ask' );
		$this->querySourceFactory = ApplicationFactory::getInstance()->getQuerySourceFactory();
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

		$this->setHeaders();
		$settings = ApplicationFactory::getInstance()->getSettings();

		$out = $this->getOutput();
		$request = $this->getRequest();
		$title = SpecialPage::getSafeTitleFor( 'Ask' );

		// A GET form submit cannot use a fragment (aka anchor) to repositioning
		// to a specific target after a request has completed, use a redirect
		// with the posted query values from the submit form to add an anchor
		// point
		if ( $settings->is( 'smwgSpecialAskFormSubmitMethod', SMW_SASK_SUBMIT_GET_REDIRECT ) && $request->getVal( '_action' ) === 'submit' ) {
			$vals = $request->getQueryValues();

			unset( $vals['_action'] );
			unset( $vals['title'] );

			return $out->redirect(
				$title->getLocalUrl( wfArrayToCGI( $vals ) . '#search' )
			);
		}

		$request->setVal( 'wpEditToken',
			$this->getUser()->getEditToken()
		);

		if ( !$GLOBALS['smwgQEnabled'] ) {
			return $out->addHtml( ErrorWidget::disabled() );
		}

		// Administrative block when used in combination with the `RemoteRequest`.
		// It is not to be mistaken with an auth block as you always can fetch
		// the content from a public wiki via cURL.
		if ( $request->getVal( 'request_type', '' ) !== '' && !$settings->isFlagSet( 'smwgRemoteReqFeatures', SMW_REMOTE_REQ_SEND_RESPONSE ) ) {
			$out->disable();
			return print RemoteRequest::SOURCE_DISABLED;
		}

		$this->init();

		if ( $request->getCheck( 'showformatoptions' ) ) {
			// handle Ajax action
			$params = $request->getArray( 'params' );
			$params['format'] = $request->getVal( 'showformatoptions' );
			$out->disable();
			echo ParametersWidget::parameterList( $params );
		} else {
			$this->extractQueryParameters( $p );

			if ( $this->isBorrowedMode ) {
				$visibleLinks = [];
			} elseif( $request->getVal( 'eq', '' ) === 'no' || $p !== null || $request->getVal( 'x' ) || $request->getVal( 'cl' ) ) {
				$visibleLinks = [ 'search', 'empty' ];
			} else {
				$visibleLinks = [ 'options', 'search', 'help', 'empty' ];
			}

			$out->addHTML(
				NavigationLinksWidget::topLinks(
					$title,
					$visibleLinks,
					$this->isEditMode
				)
			);

			$this->makeHTMLResult();
		}

		$out->addHTML( HelpWidget::html() );
		$this->addHelpLink( wfMessage( 'smw_ask_doculink' )->escaped(), true );

		// make sure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $out );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {

		if ( version_compare( MW_VERSION, '1.33', '<' ) ) {
			return 'smw_group';
		}

		// #3711, MW 1.33+
		return 'smw_group/search';
	}

	private function init() {
		$out = $this->getOutput();
		$request = $this->getRequest();

		$out->addModuleStyles( 'ext.smw.style' );
		$out->addModuleStyles( 'ext.smw.ask.styles' );
		$out->addModuleStyles( 'ext.smw.table.styles' );
		$out->addModuleStyles( 'ext.smw.page.styles' );

		$out->addModuleStyles(
			HtmlModal::getModuleStyles()
		);

		$out->addModules( 'ext.smw.ask' );
		$out->addModules( 'ext.smw.autocomplete.property' );

		$out->addModules(
			LinksWidget::getModules()
		);

		$out->addModules(
			HtmlModal::getModules()
		);

		$out->addHTML( ErrorWidget::noScript() );

		// #2590
		if ( !$this->getUser()->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			return $out->addHtml( ErrorWidget::sessionFailure() );
		}

		$settings = ApplicationFactory::getInstance()->getSettings();

		NavigationLinksWidget::setMaxInlineLimit(
			$GLOBALS['smwgQMaxInlineLimit']
		);

		FormatListWidget::setResultFormats(
			$GLOBALS['smwgResultFormats']
		);

		ParametersWidget::setTooltipDisplay(
			$this->getUser()->getOption( 'smw-prefs-ask-options-tooltip-display' )
		);

		ParametersWidget::setDefaultLimit(
			$GLOBALS['smwgQDefaultLimit']
		);

		SortWidget::setSortingSupport(
			$settings->isFlagSet( 'smwgQSortFeatures', SMW_QSORT )
		);

		// @see #835
		SortWidget::setRandSortingSupport(
			$settings->isFlagSet( 'smwgQSortFeatures', SMW_QSORT_RANDOM )
		);

		ParametersProcessor::setDefaultLimit(
			$GLOBALS['smwgQDefaultLimit']
		);

		ParametersProcessor::setMaxInlineLimit(
			$GLOBALS['smwgQMaxInlineLimit']
		);

		$this->isBorrowedMode = $request->getCheck( 'bTitle' ) || $request->getCheck( 'btitle' );
	}

	/**
	 * @param string $p
	 */
	protected function extractQueryParameters( $p ) {

		$request = $this->getRequest();
		$this->isEditMode = false;

		if ( $request->getText( 'cl', '' ) !== '' ) {
			$p = Infolink::decodeCompactLink( 'cl:' . $request->getText( 'cl' ) );
		} else {
			$p = Infolink::decodeCompactLink( $p );
		}

		list( $this->queryString, $this->parameters, $this->printouts ) = ParametersProcessor::process(
			$request,
			$p
		);

		if ( isset( $this->parameters['btitle'] ) ) {
			$this->isBorrowedMode = true;
		}

		if ( ( $request->getVal( 'eq' ) == 'yes' ) || ( $this->queryString === '' ) ) {
			$this->isEditMode = true;
		}
	}

	protected function makeHTMLResult() {

		$result = '';
		$res = null;
		$settings = ApplicationFactory::getInstance()->getSettings();
		$queryobj = null;

		$navigation = '';
		$urlArgs = $this->newUrlArgs();

		$isFromCache = false;
		$duration = 0;

		$error = '';
		$printer = null;

		if ( $this->queryString !== '' ) {
			list( $result, $res, $duration ) = $this->fetchResults(
				$printer,
				$queryobj,
				$urlArgs
			);
		}

		if ( $printer !== null && $printer->isExportFormat() ) {

			// Avoid a possible "Cannot modify header information - headers already sent by ..."
			if ( defined( 'MW_PHPUNIT_TEST' ) && method_exists( $printer, 'disableHttpHeader' ) ) {
				$printer->disableHttpHeader();
			}

			$this->getOutput()->disable();
			$request_type = $this->getRequest()->getVal( 'request_type' );

			if ( $request_type === 'embed' ) {
				// Just send a furthers link output for an embedded remote request
				echo $printer->getResult( $res, $this->params, SMW_OUTPUT_HTML ) . RemoteRequest::REQUEST_ID;
			} elseif ( $request_type === 'special_page' ) {
				// Generate raw content when being requested from a remote special_page
				echo $printer->getResult( $res, $this->params, SMW_OUTPUT_FILE ) . RemoteRequest::REQUEST_ID;
			} else {
				return $printer->outputAsFile( $res, $this->params );
			}
		}

		if ( $this->queryString ) {
			$this->getOutput()->setHTMLtitle( $this->queryString );
		} else {
			$this->getOutput()->setHTMLtitle( wfMessage( 'ask' )->text() );
		}

		$urlArgs->set( 'offset', $this->parameters['offset'] );
		$urlArgs->set( 'limit', $this->parameters['limit'] );
		$urlArgs->set( 'eq', $this->isEditMode ? 'yes' : 'no' );

		$result = Html::rawElement(
			'div',
			[
				'id' => 'result',
				'class' => 'smw-ask-result' . ( $this->isBorrowedMode ? ' is-disabled' : '' )
			],
			$result
		);

		if ( $res instanceof QueryResult ) {
			$isFromCache = $res->isFromCache();
			$error = ErrorWidget::queryError( $queryobj );
		} elseif ( is_string( $res ) ) {
			$error = $res;
		}

		$infoText = $this->getInfoText(
			$duration,
			$isFromCache
		);

		$htmlForm = new HtmlForm(
			SpecialPage::getSafeTitleFor( 'Ask' )
		);

		$htmlForm->setParameters( $this->parameters );
		$htmlForm->setQueryString( $this->queryString );
		$htmlForm->setQuery( $queryobj );

		$htmlForm->setCallbacks(
			[
				'borrowed_msg_handler' => function( &$html, &$searchInfoText ) {
					return $this->print_borrowed_msg( $html, $searchInfoText );
				},
				'code_handler' => function() {
					return $this->print_code();
				}
			]
		);

		$htmlForm->isPostSubmit(
			$settings->is( 'smwgSpecialAskFormSubmitMethod', SMW_SASK_SUBMIT_POST )
		);

		$htmlForm->isEditMode( $this->isEditMode );
		$htmlForm->isBorrowedMode( $this->isBorrowedMode );

		$form = $htmlForm->getForm(
			$urlArgs,
			$res,
			$infoText
		);

		// The overall form is "soft-disabled" so that when JS is fully
		// loaded, the ask module will remove this class and releases the form
		// for input
		$html = Html::rawElement(
			'div',
			[
				'id' => 'ask',
				"class" => ( $this->isBorrowedMode ? '' : 'is-disabled' )
			],
			$form . $error . $result
		);

		$this->getOutput()->addHTML(
			$html
		);
	}

	private function fetchResults( &$printer, &$queryobj, &$urlArgs ) {

		list( $res, $debug, $duration, $queryobj, $native_result ) = $this->getQueryResult();

		$printer = QueryProcessor::getResultPrinter(
			$this->parameters['format'],
			QueryProcessor::SPECIAL_PAGE
		);

		$printer->setShowErrors( false );

		$hidequery = $this->getRequest()->getVal( 'eq' ) == 'no';
		$request_type = $this->getRequest()->getVal( 'request_type', '' );
		$result = '';

		if ( isset( $this->parameters['request_type'] ) ) {
			$request_type = $this->parameters['request_type'];
		}

		if ( !$printer->isExportFormat() ) {
			if ( $request_type !== '' ) {
				$this->getOutput()->disable();
				$query_result = '';

				if ( $res->getCount() > 0 ) {

					if ( $request_type === 'raw' ) {
						$query_result = $printer->getResult( $res, $this->params, SMW_OUTPUT_RAW );
					} else {
						$query_result = $printer->getResult( $res, $this->params, SMW_OUTPUT_HTML );
					}

				} elseif ( $res->getCountValue() > 0 ) {
					$query_result = $res->getCountValue();
				}

				// Don't send an ID for a raw type but for all others add one
				// so that the `RemoteRequest` can respond appropriately and
				// filter those back-ends that don't send a clean output.
				if ( $request_type !== 'raw' ) {
					$query_result .= RemoteRequest::REQUEST_ID;
				}

				return print $query_result;
			} elseif ( ( $res instanceof QueryResult && $res->getCount() > 0 ) || $res instanceof StringResult ) {
				if ( $this->isEditMode ) {
					$urlArgs->set( 'eq', 'yes' );
				} elseif ( $hidequery ) {
					$urlArgs->set( 'eq', 'no' );
				}

				$query_result = $printer->getResult( $res, $this->params, SMW_OUTPUT_HTML );
				$result .= is_string( $debug ) ? $debug : '';

				if ( is_array( $query_result ) ) {
					$result .= $query_result[0];
				} else {
					$result .= $query_result;
				}
			} else {
				$result = ErrorWidget::noResult();
				$result .= is_string( $debug ) ? $debug : '';
			}
		}

		if ( $this->getRequest()->getVal( 'score_set', false ) && ( $scoreSet = $res->getScoreSet() ) !== null ) {
			$table = $scoreSet->asTable( 'sortable wikitable smwtable-striped broadtable' );

			if ( $table !== '' ) {
				$result .= '<h2>Score set</h2>' . $table;
			};
		}

		if ( $native_result !== '' ) {
			$result .= '<h2>Native result</h2>' . '<pre>' . $native_result . '</pre>';
		}

		return [ $result, $res, $duration ];
	}

	private function getInfoText( $duration, $isFromCache = false ) {

		$infoText = '';
		$source = null;

		if ( isset( $this->parameters['source'] ) ) {
			$source = $this->parameters['source'];
		}

		if ( $this->getRequest()->getVal( 'q_engine' ) === 'sql_store' ) {
			$source = 'sql_store';
		}

		$querySource = $this->querySourceFactory->toString(
			$source
		);

		if ( $duration > 0 ) {
			$infoText = Message::get(
				[ 'smw-ask-query-search-info', $this->queryString, $querySource, $isFromCache, $duration],
				Message::PARSE,
				$this->getLanguage()
			);
		}

		return $infoText;
	}

	private function print_code() {

		$code = $this->queryString ? htmlspecialchars( $this->queryString ) . "\n" : "\n";

		foreach ( $this->printouts as $printout ) {
			if ( ( $serialization = $printout->getSerialisation( true ) ) !== '' ) {
				$code .= ' |' . $serialization . "\n";
			}
		}

		foreach ( $this->params as $param ) {

			if ( !isset( $this->parameters[$param->getName()] ) ) {
				continue;
			}

			if ( !$param->wasSetToDefault() ) {
				$code .= ' |' . htmlspecialchars( $param->getName() ) . '=';
				$code .= htmlspecialchars( $this->parameters[$param->getName()] ) . "\n";
			}
		}

		return '{{#ask: ' . $code . '}}';
	}

	private function print_borrowed_msg( &$html, &$searchInfoText ) {

		if ( !$this->isBorrowedMode ) {
			return;
		}

		$borrowedMessage = $this->getRequest()->getVal( 'bMsg' );

		if ( isset( $this->parameters['bmsg'] ) ) {
			$borrowedMessage = $this->parameters['bmsg'];
		}

		$searchInfoText = '';

		if ( $borrowedMessage !== null && wfMessage( $borrowedMessage )->exists() ) {
			$html = wfMessage( $borrowedMessage, $this->queryString )->parse();
		}

		$borrowedTitle = $this->getRequest()->getVal( 'bTitle' );

		if ( isset( $this->parameters['btitle'] ) ) {
			$borrowedTitle = $this->parameters['btitle'];
		}

		if ( $borrowedTitle !== null && wfMessage( $borrowedTitle )->exists() ) {
			$this->getOutput()->setPageTitle( wfMessage( $borrowedTitle )->text() );
		}
	}

	private function newUrlArgs() {

		$urlArgs = new UrlArgs();

		// build parameter strings for URLs, based on current settings
		$urlArgs->set( 'q', $this->queryString );

		$tmp_parray = [];

		foreach ( $this->parameters as $key => $value ) {
			if ( !in_array( $key, [ 'sort', 'order', 'limit', 'offset', 'title' ] ) ) {
				$tmp_parray[$key] = $value;
			}
		}

		$urlArgs->set( 'p', Infolink::encodeParameters( $tmp_parray ) );
		$printoutstring = '';

		/**
		 * @var PrintRequest $printout
		 */
		foreach ( $this->printouts as $printout ) {
			$printoutstring .= $printout->getSerialisation( true ) . "\n";
		}

		if ( $printoutstring !== '' ) {
			$urlArgs->set( 'po', $printoutstring );
		}

		if ( array_key_exists( 'sort', $this->parameters ) ) {
			$urlArgs->set( 'sort', $this->parameters['sort'] );
		}

		if ( array_key_exists( 'order', $this->parameters ) ) {
			$urlArgs->set( 'order', $this->parameters['order'] );
		}

		if ( $this->getRequest()->getCheck( 'bTitle' ) ) {
			$urlArgs->set( 'bTitle', $this->getRequest()->getVal( 'bTitle' ) );
			$urlArgs->set( 'bMsg', $this->getRequest()->getVal( 'bMsg' ) );
		}

		if ( isset( $this->parameters['btitle'] ) ) {
			$urlArgs->set( 'bTitle', $this->parameters['btitle'] );
			$urlArgs->set( 'bMsg', $this->parameters['bmsg'] );
		}

		return $urlArgs;
	}

	private function getQueryResult() {

		$res = null;
		$debug = '';
		$duration = 0;
		$queryobj = null;
		$native_result = '';

		// Copy the printout to retain the original state while in case of no
		// specific subject (THIS) request extend the query with a
		// `PrintRequest::PRINT_THIS` column

		QueryProcessor::addThisPrintout( $this->printouts, $this->parameters );

		$params = QueryProcessor::getProcessedParams(
			$this->parameters,
			$this->printouts
		);

		$this->parameters['format'] = $params['format']->getValue();
		$this->params = $params;

		$queryobj = QueryProcessor::createQuery(
			$this->queryString,
			$params,
			QueryProcessor::SPECIAL_PAGE,
			$this->parameters['format'],
			$this->printouts
		);

		if ( $this->getRequest()->getVal( 'cache' ) === 'no' ) {
			$queryobj->setOption( SMWQuery::NO_CACHE, true );
		}

		if ( $this->getRequest()->getVal( 'native_result', false ) ) {
			$queryobj->setOption( 'native_result', true );
		}

		$queryobj->setOption( SMWQuery::PROC_CONTEXT, 'SpecialAsk' );
		$source = $params['source']->getValue();
		$noSource = $source === '';

		if ( $this->getRequest()->getVal( 'q_engine' ) === 'sql_store' ) {
			$source = 'sql_store';
		}

		$qp = [];

		foreach ( $params as $key => $value) {
			$qp[$key] = $value->getValue();
		}

		$queryobj->setOption( 'query.params', $qp );

		/**
		 * @var QueryEngine $queryEngine
		 */
		$queryEngine = $this->querySourceFactory->get(
			$source
		);

		// Measure explicit to account for a federated (sourced) query
		$duration = microtime( true );

		/**
		 * @var QueryResult $res
		 */
		$res = $queryEngine->getQueryResult(
			$queryobj
		);

		if ( $this->getRequest()->getVal( 'native_result', false ) && isset( $queryobj->native_result ) ) {
			$native_result = $queryobj->native_result;
		}

		$duration = number_format( ( microtime( true ) - $duration ), 4, '.', '' );

		// Allow to generate a debug output
		if ( $this->getRequest()->getVal( 'debug' ) && $noSource ) {

			$queryobj = QueryProcessor::createQuery(
				$this->queryString,
				$params,
				QueryProcessor::SPECIAL_PAGE,
				'debug',
				$this->printouts
			);

			$debug = $queryEngine->getQueryResult( $queryobj );
		}

		return [ $res, $debug, $duration, $queryobj, $native_result ];
	}

}
