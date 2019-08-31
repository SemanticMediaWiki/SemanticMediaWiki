<?php

namespace SMW\Query\ResultPrinters;

use Linker;
use ParamProcessor\Param;
use ParamProcessor\ParamDefinition;
use Sanitizer;
use SMW\Message;
use SMW\Parser\RecursiveTextProcessor;
use SMW\Query\Result\StringResult;
use SMW\Query\ResultPrinter as IResultPrinter;
use SMWInfolink;
use SMWOutputs as ResourceManager;
use SMWQuery;
use SMWQueryResult as QueryResult;

/**
 * Abstract base class for SMW's novel query printing mechanism. It implements
 * part of the former functionality of SMWInlineQuery (everything related to
 * output formatting and the corresponding parameters) and is subclassed by concrete
 * printers that provide the main formatting functionality.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
abstract class ResultPrinter implements IResultPrinter {

	/**
	 * Individual printers can decide what sort of deferrable mode is used for
	 * the output. `DEFERRED_DATA` signals that the format expects only the data
	 * component to be loaded from the backend.
	 */
	const DEFERRED_DATA = 'deferred.data';

	/**
	 * List of parameters, set by handleParameters.
	 * param name (lower case, trimmed) => param value (mixed)
	 *
	 * @since 1.7
	 *
	 * @var array
	 */
	protected $params;

	/**
	 * List of parameters, set by handleParameters.
	 * param name (lower case, trimmed) => Param object
	 *
	 * @since 1.8
	 *
	 * @var Param[]
	 */
	protected $fullParams;

	/**
	 * @since 1.8
	 *
	 * @var
	 */
	protected $outputMode;

	/**
	 * The query result being displayed.
	 *
	 * @since 1.8
	 *
	 * @var QueryResult
	 */
	protected $results;

	/**
	 * Text to print *before* the output in case it is *not* empty; assumed to be wikitext.
	 * Normally this is handled in SMWResultPrinter and can be ignored by subclasses.
	 */
	protected $mIntro = '';

	/**
	 * Text to print *after* the output in case it is *not* empty; assumed to be wikitext.
	 * Normally this is handled in SMWResultPrinter and can be ignored by subclasses.
	 */
	protected $mOutro = '';

	/**
	 * Text to use for link to further results, or empty if link should not be shown.
	 * Unescaped! Use @see SMWResultPrinter::getSearchLabel()
	 * and @see SMWResultPrinter::linkFurtherResults()
	 * instead of accessing this directly.
	 */
	protected $mSearchlabel = null;

	/** Default return value for empty queries. Unescaped. Normally not used in sub-classes! */
	protected $mDefault = '';

	// parameters relevant for printers in general:
	protected $mFormat; // a string identifier describing a valid format
	protected $mLinkFirst; // should article names of the first column be linked?
	protected $mLinkOthers; // should article names of other columns (besides the first) be linked?
	protected $mShowHeaders = SMW_HEADERS_SHOW; // should the headers (property names) be printed?
	protected $mShowErrors = true; // should errors possibly be printed?
	protected $mInline; // is this query result "inline" in some page (only then a link to unshown results is created, error handling may also be affected)
	protected $mLinker; // Linker object as needed for making result links. Might come from some skin at some time.

	/**
	 * List of errors that occurred while processing the parameters.
	 *
	 * @since 1.6
	 *
	 * @var array
	 */
	protected $mErrors = [];

	/**
	 * If set, treat result as plain HTML. Can be used by printer classes if wiki mark-up is not enough.
	 * This setting is used only after the result text was generated.
	 * @note HTML query results cannot be used as parameters for other templates or in any other way
	 * in combination with other wiki text. The result will be inserted on the page literally.
	 */
	protected $isHTML = false;

	/**
	 * If set, take the necessary steps to make sure that things like {{templatename| ...}} are properly
	 * processed if they occur in the result. Clearly, this is only relevant if the output is not HTML, i.e.
	 * it is ignored if SMWResultPrinter::$is_HTML is true. This setting is used only after the result
	 * text was generated.
	 * @note This requires extra processing and may make the result less useful for being used as a
	 * parameter for further parser functions. Use only if required.
	 */
	protected $hasTemplates = false;
	/// Incremented while expanding templates inserted during printout; stop expansion at some point
	private static $mRecursionDepth = 0;
	/// This public variable can be set to higher values to allow more recursion; do this at your own risk!
	/// This can be set in LocalSettings.php, but only after enableSemantics().
	public static $maxRecursionDepth = 2;

	/**
	 * @var RecursiveTextProcessor
	 */
	protected $recursiveTextProcessor;

	/**
	 * @var boolean
	 */
	private $recursiveAnnotation = false;

	/**
	 * For certaing activities (embedded pages etc.) make sure that annotations
	 * are not tranclucded (imported) into the target page when resolving a
	 * query.
	 *
	 * @var boolean
	 */
	protected $transcludeAnnotation = true;

	/**
	 * Return serialised results in specified format.
	 * Implemented by subclasses.
	 */
	abstract protected function getResultText( QueryResult $res, $outputMode );

	/**
	 * Constructor. The parameter $format is a format string
	 * that may influence the processing details.
	 *
	 * Do not override in deriving classes.
	 *
	 * @param string $format
	 * @param boolean $inline Optional since 1.9
	 */
	public function __construct( $format, $inline = true ) {
		global $smwgQDefaultLinking;

		$this->mFormat = $format;
		$this->mInline = $inline;
		$this->mLinkFirst = ( $smwgQDefaultLinking != 'none' );
		$this->mLinkOthers = ( $smwgQDefaultLinking == 'all' );
		$this->mLinker = new Linker(); ///TODO: how can we get the default or user skin here (depending on context)?
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $queryContext
	 */
	public function setQueryContext( $queryContext ) {
		$this->mInline = $queryContext != QueryContext::SPECIAL_PAGE;
	}

	/**
	 * This method is added temporary measures to avoid breaking those that relied
	 * on the removed ContextSource interface.
	 *
	 * @since 3.0
	 *
	 * @return Message
	 */
	public function msg() {
		return wfMessage( func_get_args() );
	}

	/**
	 * @since 3.0
	 *
	 * @param RecursiveTextProcessor $recursiveTextProcessor
	 */
	public function setRecursiveTextProcessor( RecursiveTextProcessor $recursiveTextProcessor ) {
		$this->recursiveTextProcessor = $recursiveTextProcessor;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function isEnabledFeature( $feature ) {
		return ( (int)$GLOBALS['smwgResultFormatsFeatures'] & $feature ) != 0;
	}

	/**
	 * @since 3.1
	 *
	 * @return Parser
	 */
	public function copyParser() {

		// Should not happen, used as fallback which in case the parser state
		// relies on the $GLOBALS['wgParser']
		if ( $this->recursiveTextProcessor === null ) {
			$this->recursiveTextProcessor = new RecursiveTextProcessor();
		}

		return $this->recursiveTextProcessor->getParser();
	}

	/**
	 * @since 3.0
	 *
	 * @param array $modules
	 * @param array $styleModules
	 */
	public function registerResources( array $modules = [], array $styleModules = [] ) {

		foreach ( $modules as $module ) {
			ResourceManager::requireResource( $module );
		}

		foreach ( $styleModules as $styleModule ) {
			ResourceManager::requireStyle( $styleModule );
		}
	}

	/**
	 * @see IResultPrinter::getResult
	 *
	 * @note: since 1.8 this method is final, since it's the entry point.
	 * Most logic has been moved out to buildResult, which you can override.
	 *
	 * @param $results QueryResult
	 * @param $fullParams array
	 * @param $outputMode integer
	 *
	 * @return string
	 */
	public final function getResult( QueryResult $results, array $fullParams, $outputMode ) {
		$this->outputMode = $outputMode;
		$this->results = $results;

		$params = [];
		$modules = [];
		$styles = [];

		/**
		 * @var \ParamProcessor\Param $param
		 */
		foreach ( $fullParams as $param ) {
			$params[$param->getName()] = $param->getValue();
		}

		$this->params = $params;
		$this->fullParams = $fullParams;

		$this->postProcessParameters();
		$this->handleParameters( $this->params, $outputMode );

		$resources = $this->getResources();

		if ( isset( $resources['modules'] ) ) {
			$modules = $resources['modules'];
		}

		if ( isset( $resources['styles'] ) ) {
			$styles = $resources['styles'];
		}

		// Register possible default modules at this point to allow for content
		// retrieved from a remote source to use required JS/CSS modules from the
		// local entry point
		$this->registerResources( $modules, $styles );

		if ( $results instanceof StringResult ) {
			$results->setOption( 'is.exportformat', $this->isExportFormat() );
			return $results->getResults();
		}

		return $this->buildResult( $results );
	}

	/**
	 * Build and return the HTML result.
	 *
	 * @since 1.8
	 *
	 * @param QueryResult $results
	 *
	 * @return string
	 */
	protected function buildResult( QueryResult $results ) {
		$this->isHTML = false;
		$this->hasTemplates = false;

		$outputMode = $this->outputMode;

		// Default output for normal printers:
		if ( $outputMode !== SMW_OUTPUT_FILE && $results->getCount() == 0 ) {
			if ( !$results->hasFurtherResults() ) {
				return $this->escapeText( $this->mDefault, $outputMode )
					. $this->getErrorString( $results );
			} elseif ( $this->mInline && $this->isDeferrable() !== self::DEFERRED_DATA ) {

				if ( !$this->linkFurtherResults( $results ) ) {
					return '';
				}

				return $this->getFurtherResultsLink( $results, $outputMode )->getText( $outputMode, $this->mLinker )
					. $this->getErrorString( $results );
			}
		}

		// Get output from printer:
		$result = $this->getResultText( $results, $outputMode );

		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			$result = $this->handleNonFileResult( $result, $results, $outputMode );
		}

		return $result;
	}

	/**
	 * Continuation of getResult that only gets executed for non file outputs.
	 *
	 * @since 1.6
	 *
	 * @param string $result
	 * @param QueryResult $results
	 * @param integer $outputmode
	 *
	 * @return string
	 */
	protected function handleNonFileResult( $result, QueryResult $results, $outputmode ) {

		 // append errors
		$result .= $this->getErrorString( $results );

		// Should not happen, used as fallback which in case the parser state
		// relies on the $GLOBALS['wgParser']
		if ( $this->recursiveTextProcessor === null ) {
			$this->recursiveTextProcessor = new RecursiveTextProcessor();
		}

		$this->recursiveTextProcessor->uniqid();

		$this->recursiveTextProcessor->setMaxRecursionDepth(
			self::$maxRecursionDepth
		);

		$this->recursiveTextProcessor->transcludeAnnotation(
			$this->transcludeAnnotation
		);

		$this->recursiveTextProcessor->setRecursiveAnnotation(
			$this->recursiveAnnotation
		);

		// Apply intro parameter
		if ( ( $this->mIntro ) && ( $results->getCount() > 0 ) ) {
			if ( $outputmode == SMW_OUTPUT_HTML ) {
				$result = Message::get( [ 'smw-parse', $this->mIntro ], Message::PARSE ) . $result;
			} elseif ( $outputmode !== SMW_OUTPUT_RAW ) {
				$result = $this->mIntro . $result;
			}
		}

		// Apply outro parameter
		if ( ( $this->mOutro ) && ( $results->getCount() > 0 ) ) {
			if ( $outputmode == SMW_OUTPUT_HTML ) {
				$result = $result . Message::get( [ 'smw-parse', $this->mOutro ], Message::PARSE );
			} elseif ( $outputmode !== SMW_OUTPUT_RAW ) {
				$result = $result . $this->mOutro;
			}
		}

		// Preprocess embedded templates if needed
		if ( ( !$this->isHTML ) && ( $this->hasTemplates ) ) {
			$result = $this->recursiveTextProcessor->recursivePreprocess( $result );
		}

		if ( ( $this->isHTML ) && ( $outputmode == SMW_OUTPUT_WIKI ) ) {
			$result = [ $result, 'isHTML' => true ];
		} elseif ( ( !$this->isHTML ) && ( $outputmode == SMW_OUTPUT_HTML ) ) {
			$result = $this->recursiveTextProcessor->recursiveTagParse( $result );
		}

		if ( $this->mShowErrors && $this->recursiveTextProcessor->getError() !== [] ) {
			$result .= Message::get( $this->recursiveTextProcessor->getError(), Message::TEXT, Message::USER_LANGUAGE );
		}

		$this->recursiveTextProcessor->releaseAnnotationBlock();

		return $result;
	}

	/**
	 * Does any additional parameter handling that needs to be done before the
	 * actual result is build. This includes cleaning up parameter values
	 * and setting class fields.
	 *
	 * Since 1.6 parameter handling should happen via validator based on the parameter
	 * definitions returned in getParameters. Therefore this method should likely
	 * not be used in any new code. It's mainly here for legacy reasons.
	 *
	 * @since 1.6
	 *
	 * @param array $params
	 * @param $outputMode
	 */
	protected function handleParameters( array $params, $outputMode ) {
		// No-op
	}

	/**
	 * Similar to handleParameters.
	 *
	 * @since 1.8
	 */
	protected function postProcessParameters() {
		$params = $this->params;

		$this->mIntro = isset( $params['intro'] ) ? str_replace( '_', ' ', $params['intro'] ) : '';
		$this->mOutro = isset( $params['outro'] ) ? str_replace( '_', ' ', $params['outro'] ) : '';

		$this->mSearchlabel = !isset( $params['searchlabel'] ) || $params['searchlabel'] === false ? null : $params['searchlabel'];
		$link = isset( $params['link'] ) ? $params['link'] : '';

		switch ( $link ) {
			case 'head': case 'subject':
				$this->mLinkFirst = true;
				$this->mLinkOthers = false;
				break;
			case 'all':
				$this->mLinkFirst = true;
				$this->mLinkOthers = true;
				break;
			case 'none':
				$this->mLinkFirst = false;
				$this->mLinkOthers = false;
				break;
		}

		$this->mDefault = isset( $params['default'] ) ? str_replace( '_', ' ', $params['default'] ) : '';
		$headers = isset( $params['headers'] ) ? $params['headers'] : '';

		if ( $headers == 'hide' ) {
			$this->mShowHeaders = SMW_HEADERS_HIDE;
		} elseif ( $headers == 'plain' ) {
			$this->mShowHeaders = SMW_HEADERS_PLAIN;
		} else {
			$this->mShowHeaders = SMW_HEADERS_SHOW;
		}

		$this->recursiveAnnotation = isset( $params['import-annotation'] ) ? $params['import-annotation'] : false;
	}

	/**
	 * Depending on current linking settings, returns a linker object
	 * for making hyperlinks or NULL if no links should be created.
	 *
	 * @param boolean $firstcol True of this is the first result column (having special linkage settings).
	 * @return Linker|null
	 */
	protected function getLinker( $firstcol = false ) {
		if ( ( $firstcol && $this->mLinkFirst ) || ( !$firstcol && $this->mLinkOthers ) ) {
			return $this->mLinker;
		} else {
			return null;
		}
	}

	/**
	 * Gets a SMWInfolink object that allows linking to a display of the query result.
	 *
	 * @since 1.8
	 *
	 * @param QueryResult $res
	 * @param $outputMode
	 * @param string $classAffix
	 *
	 * @return SMWInfolink
	 */
	protected function getLink( QueryResult $res, $outputMode, $classAffix = '' ) {
		$link = $res->getQueryLink( $this->getSearchLabel( $outputMode ) );

		if ( $classAffix !== '' ){
			$link->setStyle(  'smw-' . $this->params['format'] . '-' . Sanitizer::escapeClass( $classAffix ) );
		}

		if ( isset( $this->params['format'] ) ) {
			$link->setParameter( $this->params['format'], 'format' );
		}

		foreach ( $this->fullParams as $param ) {
			if ( !$param->wasSetToDefault() && !( $param->getName() == 'limit' && $param->getValue() === 0 ) ) {
				$link->setParameter( $param->getOriginalValue(), $param->getName() );
			}
		}

		return $link;
	}

	/**
	 * Gets a SMWInfolink object that allows linking to further results for the query.
	 *
	 * @since 1.8
	 *
	 * @param QueryResult $res
	 * @param $outputMode
	 *
	 * @return SMWInfolink
	 */
	protected function getFurtherResultsLink( QueryResult $res, $outputMode ) {
		$link = $this->getLink( $res, $outputMode, 'furtherresults' );
		$link->setParameter( $this->params['offset'] + $res->getCount(), 'offset' );
		return $link;
	}

	/**
	 * @see IResultPrinter::getQueryMode
	 *
	 * @param $context
	 *
	 * @return integer
	 */
	public function getQueryMode( $context ) {
		// TODO: Now that we are using RequestContext object maybe
		// $context is misleading
		return SMWQuery::MODE_INSTANCES;
	}

	/**
	 * @see IResultPrinter::getName
	 *
	 * @return string
	 */
	public function getName() {
		return $this->mFormat;
	}

	/**
	 * Provides a simple formatted string of all the error messages that occurred.
	 * Can be used if not specific error formatting is desired. Compatible with HTML
	 * and Wiki.
	 *
	 * @param QueryResult $res
	 *
	 * @return string
	 */
	protected function getErrorString( QueryResult $res ) {
		return $this->mShowErrors ? smwfEncodeMessages( array_merge( $this->mErrors, $res->getErrors() ) ) : '';
	}

	/**
	 * @see IResultPrinter::setShowErrors
	 *
	 * @param boolean $show
	 */
	public function setShowErrors( $show ) {
		$this->mShowErrors = $show;
	}

	/**
	 * Individual printer can override this method to allow for unified loading
	 * practice.
	 *
	 * Styles are loaded first to avoid a possible FOUC (Flash of unstyled content).
	 *
	 * @since 3.0
	 *
	 * @return []
	 */
	protected function getResources() {
		return [ 'modules' => [], 'styles' => [] ];
	}

	/**
	 * If $outputmode is SMW_OUTPUT_HTML, escape special characters occurring in the
	 * given text. Otherwise return text as is.
	 *
	 * @param string $text
	 * @param $outputmode
	 *
	 * @return string
	 */
	protected function escapeText( $text, $outputmode ) {
		return $outputmode == SMW_OUTPUT_HTML ? htmlspecialchars( $text ) : $text;
	}

	/**
	 * Get the string the user specified as a text for the "further results" link,
	 * properly escaped for the current output mode.
	 *
	 * @param $outputmode
	 *
	 * @return string
	 */
	protected function getSearchLabel( $outputmode ) {
		return $this->escapeText( $this->mSearchlabel, $outputmode );
	}

	/**
	 * Check whether a "further results" link would normally be generated for this
	 * result set with the given parameters. Individual result printers may decide to
	 * create or hide such a link independent of that, but this is the default.
	 *
	 * @param QueryResult $results
	 *
	 * @return boolean
	 */
	protected function linkFurtherResults( QueryResult $results ) {
		return $this->mInline && $results->hasFurtherResults() && $this->mSearchlabel !== '';
	}

	/**
	 * Adds an error message for a parameter handling error so a list
	 * of errors can be created later on.
	 *
	 * @since 1.6
	 *
	 * @param string $errorMessage
	 */
	protected function addError( $errorMessage ) {
		$this->mErrors[] = $errorMessage;
	}

	/**
	 * A function to describe the allowed parameters of a query using
	 * any specific format - most query printers should override this
	 * function.
	 *
	 * @deprecated since 1.8, use getParamDefinitions instead.
	 *
	 * @since 1.5
	 *
	 * @return array
	 */
	public function getParameters() {
		return [];
	}

	/**
	 * @see IResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param ParamDefinition[] $definitions
	 *
	 * @return array
	 */
	public function getParamDefinitions( array $definitions ) {
		return array_merge( $definitions, $this->getParameters() );
	}

	/**
	 * Returns the parameter definitions as an associative array where
	 * the keys hold the parameter names and point to their full definitions.
	 * array( name => array|IParamDefinition )
	 *
	 * @since 1.8
	 *
	 * @param array $definitions List of definitions to prepend to the result printers list before further processing.
	 *
	 * @return array
	 */
	public final function getNamedParameters( array $definitions = [] ) {
		$params = [];

		foreach ( $this->getParamDefinitions( $definitions ) as $param ) {
			$params[is_array( $param ) ? $param['name'] : $param->getName()] = $param;
		}

		return $params;
	}

	/**
	 * @see IResultPrinter::isExportFormat
	 *
	 * @since 1.8
	 *
	 * @return boolean
	 */
	public function isExportFormat() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isDeferrable() {
		return false;
	}

	/**
	 * Returns if the result printer supports using a "full parse" instead of a
	 * '[[SMW::off]]' . $wgParser->replaceVariables( $result ) . '[[SMW::on]]'
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function supportsRecursiveAnnotation() {
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getDefaultSort() {
		return 'ASC';
	}

}
