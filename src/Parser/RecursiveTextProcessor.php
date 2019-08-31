<?php

namespace SMW\Parser;

use Parser;
use ParserOptions;
use RuntimeException;
use SMW\Localizer;
use SMW\ParserData;
use SMW\MediaWiki\Template\TemplateExpander;
use Title;

/**
 * @private
 *
 * Helper class in processing content that requires to be parsed internally and
 * recursively mostly in connection with templates.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RecursiveTextProcessor {

	/**
	 * @see Special:ExpandTemplates
	 * @var int Maximum size in bytes to include. 50MB allows fixing those huge pages
	 */
	const MAX_INCLUDE_SIZE = 50000000;

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * Incremented while expanding templates inserted during printout; stop
	 * expansion at some point
	 *
	 * @var integer
	 */
	private $recursionDepth = 0;

	/**
	 * @var integer
	 */
	private $maxRecursionDepth = 2;

	/**
	 * @var boolean
	 */
	private $recursiveAnnotation = false;

	/**
	 * @var integer
	 */
	private $uniqid;

	/**
	 * @var string
	 */
	private $error = [];

	/**
	 * @since 3.0
	 *
	 * @param Parser $parser|null
	 */
	public function __construct( Parser $parser = null ) {
		$this->parser = $parser;

		if ( $this->parser === null ) {
			$this->parser = $GLOBALS['wgParser'];
		}
	}

	/**
	 * @since 3.0
	 *
	 * @return Parser
	 */
	public function getParser() {
		return $this->parser;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Track recursive processing
	 *
	 * @since 3.0
	 *
	 * @param string|integer|null $uniqid
	 */
	public function uniqid( $uniqid = null ) {

		if ( $uniqid === null ) {
			$uniqid = uniqid();
		}

		$this->uniqid = $uniqid;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $maxRecursionDepth
	 */
	public function setMaxRecursionDepth( $maxRecursionDepth ) {
		$this->maxRecursionDepth = $maxRecursionDepth;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $transcludeAnnotation
	 */
	public function transcludeAnnotation( $transcludeAnnotation ) {

		if ( $this->parser->getOutput() === null || $transcludeAnnotation === true ) {
			return;
		}

		if ( $this->uniqid === null ) {
			throw new RuntimeException( "Expected a uniqid and not null." );
		}

		$parserOutput = $this->parser->getOutput();
		$track = $parserOutput->getExtensionData( ParserData::ANNOTATION_BLOCK );

		if ( $track === null ) {
			$track = [];
		}

		// Track each embedded #ask process to ensure to remove
		// blocks on the correct recursive iteration (e.g Page A containing
		// #ask is transcluded in Page B using a #ask -> is embedded ...
		// etc.)
		$track[$this->uniqid] = true;

		$parserOutput->setExtensionData( ParserData::ANNOTATION_BLOCK, $track );
	}

	/**
	 * @since 3.0
	 */
	public function releaseAnnotationBlock() {

		if ( $this->parser->getOutput() === null ) {
			return;
		}

		$parserOutput = $this->parser->getOutput();
		$track = $parserOutput->getExtensionData( ParserData::ANNOTATION_BLOCK );

		if ( $track !== [] ) {
			unset( $track[$this->uniqid] );
		}

		// No recursive tracks left, set it to false
		if ( $track === [] ) {
			$track = false;
		}

		$parserOutput->setExtensionData( ParserData::ANNOTATION_BLOCK, $track );
	}

	/**
	 * @since 3.0
	 */
	public function releaseAnyAnnotationBlock() {
		if ( $this->parser->getOutput() !== null ) {
			$this->parser->getOutput()->setExtensionData( ParserData::ANNOTATION_BLOCK, false );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $recursiveAnnotation
	 */
	public function setRecursiveAnnotation( $recursiveAnnotation ) {
		$this->recursiveAnnotation = (bool)$recursiveAnnotation;
	}

	/**
	 * @since 3.0
	 *
	 * @param ParserData $parserData
	 */
	public function copyData( ParserData $parserData ) {
		if ( $this->recursiveAnnotation ) {
			$parserData->importFromParserOutput( $this->parser->getOutput() );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function expandTemplate( $template ) {

		$templateExpander = new TemplateExpander(
			$this->parser
		);

		return $templateExpander->expand( $template );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function recursivePreprocess( $text ) {

		// not during parsing, no preprocessing needed, still protect the result
		if ( $this->parser === null || !$this->parser->getTitle() instanceof Title || !$this->parser->getOptions() instanceof ParserOptions ) {
			return $this->recursiveAnnotation ? $text : '[[SMW::off]]' . $text . '[[SMW::on]]';
		}

		$this->recursionDepth++;

		// restrict recursion
		if ( $this->recursionDepth <= $this->maxRecursionDepth && $this->recursiveAnnotation ) {
			$text =  $this->parser->recursivePreprocess( $text );
		} elseif ( $this->recursionDepth <= $this->maxRecursionDepth ) {
			$text = '[[SMW::off]]' . $this->parser->replaceVariables( $text ) . '[[SMW::on]]';
		} else {
			$this->error = [ 'smw-parser-recursion-level-exceeded', $this->maxRecursionDepth ];
			$text = '';
		}

		// During a block request remove any categories from the text since we
		// cannot block the annotation during a parse, this ensures that
		// categories don't appear in the source text and hereby in any successive
		// parse
		$this->pruneCategory( $text );

		$this->recursionDepth--;

		return $text;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function recursiveTagParse( $text ) {

		if ( $this->parser === null ) {
			throw new RuntimeException( 'Missing a parser instance!' );
		}

		$this->recursionDepth++;
		$isValid = $this->parser->getTitle() instanceof Title && $this->parser->getOptions() instanceof ParserOptions;

		if ( $this->recursionDepth <= $this->maxRecursionDepth && $isValid ) {
				$text = $this->parser->recursiveTagParse( $text );
		} elseif ( $this->recursionDepth <= $this->maxRecursionDepth ) {
			$title = $GLOBALS['wgTitle'];

			if ( $title === null ) {
				$title = Title::newFromText( 'UNKNOWN_TITLE' );
			}

			$popt = new ParserOptions();

			// FIXME: Remove the if block once compatibility with MW <1.31 is dropped
			if ( ! defined( '\ParserOutput::SUPPORTS_STATELESS_TRANSFORMS' ) || \ParserOutput::SUPPORTS_STATELESS_TRANSFORMS !== 1 ) {
				$popt->setEditSection( false );
			}
			$parserOutput = $this->parser->parse( $text . '__NOTOC__', $title, $popt );

			// Maybe better to use Parser::recursiveTagParseFully ??

			/// NOTE: as of MW 1.14SVN, there is apparently no better way to hide the TOC
			\SMWOutputs::requireFromParserOutput( $parserOutput );
			$text = $parserOutput->getText( [ 'enableSectionEditLinks' => false ] );
		} else {
			$this->error = [ 'smw-parser-recursion-level-exceeded', $this->maxRecursionDepth ];
			$text = '';
		}

		$this->recursionDepth--;

		return $text;
	}

	private function pruneCategory( &$text ) {

		if ( $this->parser->getOutput() === null ) {
			return;
		}

		$parserOutput = $this->parser->getOutput();

		if ( ( $track = $parserOutput->getExtensionData( ParserData::ANNOTATION_BLOCK ) ) === false ) {
			return;
		}

		// Content language dep. category name
		$category = Localizer::getInstance()->getNamespaceTextById(
			NS_CATEGORY
		);

		if ( isset( $track[$this->uniqid] ) ) {
			$text = preg_replace(
				"/\[\[(Category|{$category}):(.*)\]\]/U",
				'',
				$text
			);
		}
	}

}
