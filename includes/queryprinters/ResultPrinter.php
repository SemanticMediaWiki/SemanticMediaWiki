<?php

namespace SMW;

use Linker;
use ParamProcessor\ParamDefinition;
use ParserOptions;
use Sanitizer;
use SMWInfolink;
use SMWQuery;
use SMWQueryResult;
use Title;

/**
 * Abstract base class for printing query results.
 *
 * @since 1.9
 *
 * @ingroup SMWQuery
 *
 * @licence GNU GPL v2 or later
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */

/**
 * This group contains all members that are relate to query formatting and
 * printing.
 *
 * @defgroup QueryPrinter QueryPrinter
 * @ingroup SMWQuery
 */

/**
 * Abstract base class for SMW's novel query printing mechanism. It implements
 * part of the former functionality of SMWInlineQuery (everything related to
 * output formatting and the corresponding parameters) and is subclassed by concrete
 * printers that provide the main formatting functionality.
 *
 * @ingroup SMWQuery
 */
abstract class ResultPrinter extends \ContextSource implements QueryResultPrinter {

	/**
	 * @deprecated Use $params instead. Will be removed in 1.10.
	 */
	protected $m_params;

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
	 * param name (lower case, trimmed) => IParam object
	 *
	 * @since 1.8
	 *
	 * @var \IParam[]
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
	 * @var SMWQueryResult
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
	protected $mErrors = array();

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
	 * Return serialised results in specified format.
	 * Implemented by subclasses.
	 */
	abstract protected function getResultText( SMWQueryResult $res, $outputMode );

	/**
	 * Constructor. The parameter $format is a format string
	 * that may influence the processing details.
	 *
	 * Do not override in deriving classes.
	 *
	 * @param string $format
	 * @param boolean $inline Optional since 1.9
	 * @param boolean $useValidator Deprecated since 1.6.2, removal in 1.10
	 */
	public function __construct( $format, $inline = true, $useValidator = false ) {
		global $smwgQDefaultLinking;

		// Context aware since SMW 1.9
		//
		// If someone cleans the constructor, please add
		// IContextSource $context = null as for now we leave it
		// in order to keep compatibility with the original constructor
		$this->setContext( \RequestContext::getMain() );

		$this->mFormat = $format;
		$this->mInline = $inline;
		$this->mLinkFirst = ( $smwgQDefaultLinking != 'none' );
		$this->mLinkOthers = ( $smwgQDefaultLinking == 'all' );
		$this->mLinker = new Linker(); ///TODO: how can we get the default or user skin here (depending on context)?
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
	 * @see SMWIResultPrinter::getResult
	 *
	 * @note: since 1.8 this method is final, since it's the entry point.
	 * Most logic has been moved out to buildResult, which you can override.
	 *
	 * @param $results SMWQueryResult
	 * @param $fullParams array
	 * @param $outputMode integer
	 *
	 * @return string
	 */
	public final function getResult( SMWQueryResult $results, array $fullParams, $outputMode ) {
		$this->outputMode = $outputMode;
		$this->results = $results;

		$params = array();

		/**
		 * @var \IParam $param
		 */
		foreach ( $fullParams as $param ) {
			$params[$param->getName()] = $param->getValue();
		}

		$this->params = $params;
		$this->m_params = $params; // Compat, change made in 1.6.3/1.7, removal in 1.10
		$this->fullParams = $fullParams;

		$this->postProcessParameters();
		$this->handleParameters( $this->params, $outputMode );

		return $this->buildResult( $results );
	}

	/**
	 * Build and return the HTML result.
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $results
	 *
	 * @return string
	 */
	protected function buildResult( SMWQueryResult $results ) {
		$this->isHTML = false;
		$this->hasTemplates = false;

		$outputMode = $this->outputMode;

		// Default output for normal printers:
		if ( $outputMode !== SMW_OUTPUT_FILE && $results->getCount() == 0 ) {
			if ( !$results->hasFurtherResults() ) {
				return $this->escapeText( $this->mDefault, $outputMode )
					. $this->getErrorString( $results );
			} elseif ( $this->mInline ) {

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
	 * @param SMWQueryResult $results
	 * @param integer $outputmode
	 *
	 * @return string
	 */
	protected function handleNonFileResult( $result, SMWQueryResult $results, $outputmode ) {
		/**
		 * @var \Parser $wgParser
		 */
		global $wgParser;

		$result .= $this->getErrorString( $results ); // append errors

		// MW 1.21+
		// Block recursive import of annotations unless otherwise specified for
		// a specific use case
		if ( method_exists( $wgParser->getOutput(), 'setExtensionData' ) ) {
			$wgParser->getOutput()->setExtensionData(
				'smw-blockannotation',
				$this->params['format'] === 'embedded'
			);
		}

		// Apply intro parameter
		if ( ( $this->mIntro ) && ( $results->getCount() > 0 ) ) {
			if ( $outputmode == SMW_OUTPUT_HTML ) {
				$result = Message::get( array( 'smw-parse', $this->mIntro ), Message::PARSE ) . $result;
			} else {
				$result = $this->mIntro . $result;
			}
		}

		// Apply outro parameter
		if ( ( $this->mOutro ) && ( $results->getCount() > 0 ) ) {
			if ( $outputmode == SMW_OUTPUT_HTML ) {
				$result = $result . Message::get( array( 'smw-parse', $this->mOutro ), Message::PARSE );
			} else {
				$result = $result . $this->mOutro;
			}
		}

		// Preprocess embedded templates if needed
		if ( ( !$this->isHTML ) && ( $this->hasTemplates ) ) {
			if ( ( $wgParser->getTitle() instanceof Title ) && ( $wgParser->getOptions() instanceof ParserOptions ) ) {
				self::$mRecursionDepth++;

				if ( self::$mRecursionDepth <= self::$maxRecursionDepth ) { // restrict recursion
					$result = isset( $this->params['import-annotation'] ) && $this->params['import-annotation'] ? $wgParser->recursivePreprocess( $result ) : '[[SMW::off]]' . $wgParser->replaceVariables( $result ) . '[[SMW::on]]';
				} else {
					$result = ''; /// TODO: explain problem (too much recursive parses)
				}

				self::$mRecursionDepth--;
			} else { // not during parsing, no preprocessing needed, still protect the result
				$result = isset( $this->params['import-annotation'] ) && $this->params['import-annotation'] ? $result : '[[SMW::off]]' . $result . '[[SMW::on]]';
			}
		}

		if ( ( $this->isHTML ) && ( $outputmode == SMW_OUTPUT_WIKI ) ) {
			$result = array( $result, 'isHTML' => true );
		} elseif ( ( !$this->isHTML ) && ( $outputmode == SMW_OUTPUT_HTML ) ) {
			self::$mRecursionDepth++;

			// check whether we are in an existing parse, or if we should start a new parse for $wgTitle
			if ( self::$mRecursionDepth <= self::$maxRecursionDepth ) { // retrict recursion
				if ( ( $wgParser->getTitle() instanceof Title ) && ( $wgParser->getOptions() instanceof ParserOptions ) ) {
					$result = $wgParser->recursiveTagParse( $result );
				} else {
					global $wgTitle;

					$popt = new ParserOptions();
					$popt->setEditSection( false );
					$pout = $wgParser->parse( $result . '__NOTOC__', $wgTitle, $popt );

					/// NOTE: as of MW 1.14SVN, there is apparently no better way to hide the TOC
					\SMWOutputs::requireFromParserOutput( $pout );
					$result = $pout->getText();
				}
			} else {
				$result = ''; /// TODO: explain problem (too much recursive parses)
			}

			self::$mRecursionDepth--;
		}

		if ( method_exists( $wgParser->getOutput(), 'setExtensionData' ) ) {
			$wgParser->getOutput()->setExtensionData(
				'smw-blockannotation',
				false
			);
		}

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

		$this->mIntro = str_replace( '_', ' ', $params['intro'] );
		$this->mOutro = str_replace( '_', ' ', $params['outro'] );

		$this->mSearchlabel = $params['searchlabel'] === false ? null : $params['searchlabel'];

		switch ( $params['link'] ) {
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

		 $this->mDefault = str_replace( '_', ' ', $params['default'] );

		if ( $params['headers'] == 'hide' ) {
			$this->mShowHeaders = SMW_HEADERS_HIDE;
		} elseif ( $params['headers'] == 'plain' ) {
			$this->mShowHeaders = SMW_HEADERS_PLAIN;
		} else {
			$this->mShowHeaders = SMW_HEADERS_SHOW;
		}
	}

	/**
	 * Depending on current linking settings, returns a linker object
	 * for making hyperlinks or NULL if no links should be created.
	 *
	 * @param boolean $firstcol True of this is the first result column (having special linkage settings).
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
	 * @param SMWQueryResult $res
	 * @param $outputMode
	 * @param string $classAffix
	 *
	 * @return SMWInfolink
	 */
	protected function getLink( SMWQueryResult $res, $outputMode, $classAffix = '' ) {
		$link = $res->getQueryLink( $this->getSearchLabel( $outputMode ) );

		if ( $classAffix !== '' ){
			$link->setStyle(  'smw-' . $this->params['format'] . '-' . Sanitizer::escapeClass( $classAffix ) );
		}

		$link->setParameter( $this->params['format'], 'format' );

		/**
		 * @var \IParam $param
		 */
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
	 * @param SMWQueryResult $res
	 * @param $outputMode
	 *
	 * @return SMWInfolink
	 */
	protected function getFurtherResultsLink( SMWQueryResult $res, $outputMode ) {
		$link = $this->getLink( $res, $outputMode, 'furtherresults' );
		$link->setParameter( $this->params['offset'] + $res->getCount(), 'offset' );
		return $link;
	}

	/**
	 * @see SMWIResultPrinter::getQueryMode
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
	 * @see SMWIResultPrinter::getName
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
	 * @param SMWQueryResult $res
	 *
	 * @return string
	 */
	protected function getErrorString( SMWQueryResult $res ) {
		return $this->mShowErrors ? smwfEncodeMessages( array_merge( $this->mErrors, $res->getErrors() ) ) : '';
	}

	/**
	 * @see SMWIResultPrinter::setShowErrors
	 *
	 * @param boolean $show
	 */
	public function setShowErrors( $show ) {
		$this->mShowErrors = $show;
	}

	/**
	 * If $outputmode is SMW_OUTPUT_HTML, escape special characters occuring in the
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
	 * @param SMWQueryResult $results
	 *
	 * @return boolean
	 */
	protected function linkFurtherResults( SMWQueryResult $results ) {
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
	 * Return an array describing the parameters of specifically text-based
	 * formats, like 'list' and 'table', for use in their getParameters()
	 * functions
	 *
	 * @deprecated since 1.8, removal in 1.10
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	protected function textDisplayParameters() {
		return array();
	}

	/**
	 * Return an array describing the parameters of the export formats
	 * like 'rss' and 'csv', for use in their getParameters() functions
	 *
	 * @deprecated since 1.8, removal in 1.10
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	protected function exportFormatParameters() {
		return array();
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
		return array();
	}

	/**
	 * @see SMWIResultPrinter::getParamDefinitions
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
	public final function getNamedParameters( array $definitions = array() ) {
		$params = array();

		foreach ( $this->getParamDefinitions( $definitions ) as $param ) {
			$params[is_array( $param ) ? $param['name'] : $param->getName()] = $param;
		}

		return $params;
	}

	/**
	 * @see SMWIResultPrinter::isExportFormat
	 *
	 * @since 1.8
	 *
	 * @return boolean
	 */
	public function isExportFormat() {
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
