<?php

namespace SMW;

use ParserOptions;
use Revision;
use Parser;
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
	 * @since 1.9.0.3
	 *
	 * @return ContentParser
	 */
	public function setRevision( Revision $revision = null ) {
		$this->revision = $revision;
		return $this;
	}

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Returns ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return ParserOutput|null
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * Returns collected errors occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
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

		if ( $this->hasContentHandler() ) {
			return $this->fetchFromContent();
		}

		return $this->fetchFromParser();
	}

	/**
	 * Parsing text
	 *
	 * @since 1.9
	 */
	protected function parseText( $text ) {
		Profiler::In( __METHOD__ );

		$this->parserOutput = $this->parser->parse(
			$text,
			$this->getTitle(),
			$this->newParserOptions()
		);

		Profiler::Out( __METHOD__ );
		return $this;
	}

	/**
	 * Using the content handler to return a ParserOutput object
	 *
	 * @note Revision ID must be passed to the parser output to
	 * get revision variables correct
	 *
	 * @note If no content is available create an empty object
	 *
	 * @since 1.9
	 */
	protected function fetchFromContent() {
		Profiler::In( __METHOD__ );

		if ( $this->getRevision() !== null ) {

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

		} else {
			$this->errors = array( __METHOD__ . " No revision available for {$this->getTitle()->getPrefixedDBkey()}" );
		}

		Profiler::Out( __METHOD__ );
		return $this;
	}

	/**
	 * Re-parsing page content from a revision
	 *
	 * @since 1.9
	 */
	protected function fetchFromParser() {
		Profiler::In( __METHOD__ );

		if ( $this->getRevision() !== null ) {

			$this->parserOutput = $this->parser->parse(
				$this->getRevision()->getText(),
				$this->getTitle(),
				$this->newParserOptions(),
				true,
				true,
				$this->getRevision()->getID()
			);

		} else {
			$this->errors = array( __METHOD__ . " No revision available for {$this->getTitle()->getPrefixedDBkey()}" );
		}

		Profiler::Out( __METHOD__ );
		return $this;
	}

	/**
	 * @note ContentHandler does not exist prior MW 1.21
	 *
	 * @return boolean
	 */
	protected function hasContentHandler() {
		return class_exists( 'ContentHandler' );
	}

	/**
	 * @return ParserOptions
	 */
	protected function newParserOptions() {

		$user = null;

		if ( $this->getRevision() !== null ) {
			$user = User::newFromId( $this->getRevision()->getUser() );
		}

		return new ParserOptions( $user );
	}

	/**
	 * @note Revision::READ_NORMAL is not defined in MW 1.19
	 *
	 * @return Revision
	 */
	protected function getRevision() {

		if ( $this->revision instanceOf Revision ) {
			return $this->revision;
		}

		if ( defined( 'Revision::READ_NORMAL' ) ) {
			$this->revision = Revision::newFromTitle( $this->getTitle(), false, Revision::READ_NORMAL );
		} else {
			$this->revision = Revision::newFromTitle( $this->getTitle() );
		}

		return $this->revision;
	}

}
