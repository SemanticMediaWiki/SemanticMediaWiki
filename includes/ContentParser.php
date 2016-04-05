<?php

namespace SMW;

use Parser;
use ParserOptions;
use Revision;
use Title;
use User;

/**
 * Fetches the ParserOutput either by parsing an invoked text component,
 * re-parsing a text revision, or accessing the ContentHandler to generate a
 * ParserOutput object
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ContentParser {

	/** @var Title */
	protected $title;

	/** @var Parser */
	protected $parser = null;

	/** @var ParserOutput */
	protected $parserOutput = null;

	/** @var Revision */
	protected $revision = null;

	/** @var array */
	protected $errors = array();

	/**
	 * @var boolean
	 */
	private $enabledToUseContentHandler = true;

	/**
	 * @var boolean
	 */
	private $skipInTextAnnotationParser = false;

	/**
	 * @note Injecting new Parser() alone will not yield an expected result and
	 * doing new Parser( $GLOBALS['wgParserConf'] brings no benefits therefore
	 * we stick to the GLOBAL as fallback if no parser is injected.
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param Parser|null $parser
	 */
	public function __construct( Title $title, Parser $parser = null ) {
		$this->title  = $title;
		$this->parser = $parser;

		if ( $this->parser === null ) {
			$this->parser = $GLOBALS['wgParser'];
		}
	}

	/**
	 * @since 2.3
	 *
	 * @return Parser $parser
	 */
	public function setParser( Parser $parser ) {
		$this->parser = $parser;
	}

	/**
	 * @since 1.9.1
	 *
	 * @return ContentParser
	 */
	public function setRevision( Revision $revision = null ) {
		$this->revision = $revision;
		return $this;
	}

	/**
	 * @bug 62856 and #212
	 *
	 * @since 2.0
	 */
	public function forceToUseParser() {
		$this->enabledToUseContentHandler = false;
	}

	/**
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @since 1.9
	 *
	 * @return ParserOutput|null
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	public function skipInTextAnnotationParser() {
		return $this->skipInTextAnnotationParser = true;
	}

	/**
	 * Generates or fetches the ParserOutput object from an appropriate source
	 *
	 * @since 1.9
	 *
	 * @param string|null $text
	 *
	 * @return ContentParser
	 */
	public function parse( $text = null ) {

		if ( $text !== null ) {
			return $this->parseText( $text );
		}

		if ( $this->hasContentHandler() && $this->enabledToUseContentHandler ) {
			return $this->fetchFromContent();
		}

		return $this->fetchFromParser();
	}

	protected function parseText( $text ) {

		$this->parserOutput = $this->parser->parse(
			$text,
			$this->getTitle(),
			$this->makeParserOptions()
		);

		return $this;
	}

	/**
	 * @note Revision ID must be passed to the parser output to
	 * get revision variables correct
	 *
	 * @note If no content is available create an empty object
	 */
	protected function fetchFromContent() {

		if ( $this->getRevision() === null ) {
			return $this->msgForNullRevision();
		}

		$content = $this->getRevision()->getContent( Revision::RAW );

		if ( !$content ) {
			$content = $this->getRevision()->getContentHandler()->makeEmptyContent();
		}

		$this->parserOutput = $content->getParserOutput(
			$this->getTitle(),
			$this->getRevision()->getId(),
			null,
			true
		);

		return $this;
	}

	protected function fetchFromParser() {

		if ( $this->getRevision() === null ) {
			return $this->msgForNullRevision();
		}

		$this->parserOutput = $this->parser->parse(
			$this->getRevision()->getText(),
			$this->getTitle(),
			$this->makeParserOptions(),
			true,
			true,
			$this->getRevision()->getID()
		);

		return $this;
	}

	protected function msgForNullRevision( $fname = __METHOD__ ) {
		$this->errors = array( $fname . " No revision available for {$this->getTitle()->getPrefixedDBkey()}" );
		return $this;
	}

	protected function makeParserOptions() {

		$user = null;

		if ( $this->getRevision() !== null ) {
			$user = User::newFromId( $this->getRevision()->getUser() );
		}

		$parserOptions = new ParserOptions( $user );

		// Use the InterfaceMessage marker to skip InTextAnnotationParser
		// processing
		$parserOptions->setInterfaceMessage( $this->skipInTextAnnotationParser );

		return $parserOptions;
	}

	protected function getRevision() {

		if ( $this->revision instanceof Revision ) {
			return $this->revision;
		}

		// Revision::READ_NORMAL is not specified in MW 1.19
		if ( defined( 'Revision::READ_NORMAL' ) ) {
			$this->revision = Revision::newFromTitle( $this->getTitle(), false, Revision::READ_NORMAL );
		} else {
			$this->revision = Revision::newFromTitle( $this->getTitle() );
		}

		return $this->revision;
	}

	protected function hasContentHandler() {
		return defined( 'CONTENT_MODEL_WIKITEXT' );
	}

}
