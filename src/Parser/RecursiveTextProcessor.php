<?php

namespace SMW\Parser;

use Error;
use MediaWiki\MediaWikiServices;
use Parser;
use ParserOptions;
use ParserOutput;
use RequestContext;
use RuntimeException;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\Template\TemplateExpander;
use SMW\ParserData;
use SMWOutputs;
use Title;

/**
 * @private
 *
 * Helper class in processing content that requires to be parsed internally and
 * recursively mostly in connection with templates.
 *
 * @license GPL-2.0-or-later
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
	 * @var int
	 */
	private $recursionDepth = 0;

	/**
	 * @var int
	 */
	private $maxRecursionDepth = 2;

	/**
	 * @var bool
	 */
	private $recursiveAnnotation = false;

	/**
	 * @var int
	 */
	private $uniqid;

	/**
	 * @var array
	 */
	private $error = [];

	/**
	 * @since 3.0
	 *
	 * @param Parser|null $parser
	 */
	public function __construct( ?Parser $parser = null ) {
		$this->parser = $parser ?? MediaWikiServices::getInstance()->getParser();
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
	 * @return
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Track recursive processing
	 *
	 * @since 3.0
	 *
	 * @param string|int|null $uniqid
	 */
	public function uniqid( $uniqid = null ) {
		$this->uniqid = $uniqid ?? uniqid();
	}

	/**
	 * @since 3.0
	 *
	 * @param int $maxRecursionDepth
	 */
	public function setMaxRecursionDepth( $maxRecursionDepth ) {
		$this->maxRecursionDepth = $maxRecursionDepth;
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $transcludeAnnotation
	 */
	public function transcludeAnnotation( $transcludeAnnotation ) {
		$parserOutput = $this->getParserOutputSafe();

		if ( !$parserOutput || $transcludeAnnotation === true ) {
			return;
		}

		if ( $this->uniqid === null ) {
			throw new RuntimeException( "Expected a uniqid and not null." );
		}

		$track = $parserOutput->getExtensionData( ParserData::ANNOTATION_BLOCK ) ?: [];

		// Track each embedded #ask process to ensure to remove
		// blocks on the correct recursive iteration (e.g. Page A containing
		// #ask is transcluded in Page B using a #ask -> is embedded ...
		// etc.)
		$track[$this->uniqid] = true;

		$parserOutput->setExtensionData( ParserData::ANNOTATION_BLOCK, $track );
	}

	/**
	 * @since 3.0
	 */
	public function releaseAnnotationBlock() {
		$parserOutput = $this->getParserOutputSafe();

		if ( !$parserOutput ) {
			return;
		}

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
	 *
	 * @param bool $recursiveAnnotation
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
			$parserData->importFromParserOutput( $this->getParserOutputSafe() );
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
		if ( !$this->parser || !$this->parser->getOptions() || !$this->parser->getTitle() ) {
			return $this->recursiveAnnotation ? $text : '[[SMW::off]]' . $text . '[[SMW::on]]';
		}

		$this->recursionDepth++;

		// restrict recursion
		if ( $this->recursionDepth <= $this->maxRecursionDepth && $this->recursiveAnnotation ) {
			$text = $this->parser->recursivePreprocess( $text );
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
		$isValid = $this->parser->getOptions() && $this->parser->getTitle();

		if ( $this->recursionDepth <= $this->maxRecursionDepth && $isValid ) {
			$text = $this->parser->recursiveTagParse( $text );
		} elseif ( $this->recursionDepth <= $this->maxRecursionDepth ) {
			$title = $GLOBALS['wgTitle'] ?? Title::newFromText( 'UNKNOWN_TITLE' );

			$user = RequestContext::getMain()->getUser();
			$popt = new ParserOptions( $user );

			// Maybe better to use Parser::recursiveTagParseFully ??
			/// NOTE: as of MW 1.14SVN, there is apparently no better way to hide the TOC
			$parserOutput = $this->parser->parse( $text . '__NOTOC__', $title, $popt );

			SMWOutputs::requireFromParserOutput( $parserOutput );
			$text = $parserOutput->getText( [ 'enableSectionEditLinks' => false ] );
		} else {
			$this->error = [ 'smw-parser-recursion-level-exceeded', $this->maxRecursionDepth ];
			$text = '';
		}

		$this->recursionDepth--;

		return $text;
	}

	private function pruneCategory( &$text ) {
		$parserOutput = $this->getParserOutputSafe();
		if ( !$parserOutput ) {
			return;
		}

		if ( ( $track = $parserOutput->getExtensionData( ParserData::ANNOTATION_BLOCK ) ) === false ) {
			return;
		}

		// Content language dep. category name
		$category = Localizer::getInstance()->getNsText(
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

	private function getParserOutputSafe(): ?ParserOutput {
		try {
			return $this->parser->getOutput();
		} catch ( Error $e ) {
			return null;
		}
	}

}
