<?php

namespace SMW\Parser;

use Parser;
use Title;
use ParserOptions;
use ParserOutput;
use RuntimeException;
use SMW\ParserData;

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
		if ( $this->parser->getOutput() !== null ) {
			$this->parser->getOutput()->setExtensionData( ParserData::ANNOTATION_BLOCK, !(bool)$transcludeAnnotation );
		}
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
	 * @param string $text
	 *
	 * @return text
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

		$this->recursionDepth--;

		return $text;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public function recursiveTagParse( $text ) {

		if ( $this->parser === null ) {
			throw new RuntimeException( 'Missing a parser instance' );
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
			$popt->setEditSection( false );
			$parserOutput = $this->parser->parse( $text . '__NOTOC__', $title, $popt );

			// Maybe better to use Parser::recursiveTagParseFully ??

			/// NOTE: as of MW 1.14SVN, there is apparently no better way to hide the TOC
			\SMWOutputs::requireFromParserOutput( $parserOutput );
			$text = $parserOutput->getText();
		} else {
			$this->error = [ 'smw-parser-recursion-level-exceeded', $this->maxRecursionDepth ];
			$text = '';
		}

		$this->recursionDepth--;

		return $text;
	}

}
