<?php

namespace SMW;

use Parser;
use ParserOptions;
use Revision;
use Title;
use User;

/**
 * Parse page content and generating a ParserOutput object
 *
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
			$this->parseText( $text );
		} elseif ( $this->hasContentHandler() ) {
			 $this->fetchFromContent( $this->newRevision() );
		} else {
			$this->fetchFromParser( $this->newRevision() );
		}

		return $this;
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
	protected function fetchFromContent( $revision ) {
		Profiler::In( __METHOD__ );

		if ( $revision !== null ) {

			$content = $revision->getContent( Revision::RAW );

			if ( !$content ) {
				$content = $revision->getContentHandler()->makeEmptyContent();
			}

			$this->parserOutput = $content->getParserOutput(
				$this->getTitle(),
				$revision->getId(),
				null,
				true
			);

		} else {
			$this->errors = array( __METHOD__ . " No revision available for {$this->getTitle()->getPrefixedDBkey()}" );
		}

		Profiler::Out( __METHOD__ );
	}

	/**
	 * Re-parsing page content from a revision
	 *
	 * @since 1.9
	 */
	protected function fetchFromParser( $revision ) {
		Profiler::In( __METHOD__ );

		if ( $revision !== null ) {

			$this->parserOutput = $this->parser->parse(
				$revision->getText(),
				$this->getTitle(),
				$this->newParserOptions( $revision ),
				true,
				true,
				$revision->getID()
			);

		} else {
			$this->errors = array( __METHOD__ . " No revision available for {$this->getTitle()->getPrefixedDBkey()}" );
		}

		Profiler::Out( __METHOD__ );
	}

	/**
	 * @note ContentHandler does not exist prior MW 1.21
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	protected function hasContentHandler() {
		return class_exists( 'ContentHandler');
	}

	/**
	 * @since  1.9
	 *
	 * @return ParserOptions
	 */
	protected function newParserOptions( $revision = null ) {

		$user = null;

		if ( $revision !== null ) {
			$user = User::newFromId( $revision->getUser() );
		}

		return new ParserOptions( $user );
	}

	/**
	 * @note Revision::READ_NORMAL is not defined in MW 1.19
	 *
	 * @since  1.9
	 *
	 * @return Revision
	 */
	protected function newRevision() {

		$revision = null;

		if ( defined( 'Revision::READ_NORMAL' ) ) {
			$revision = Revision::newFromTitle( $this->getTitle(), false, Revision::READ_NORMAL );
		} else {
			$revision = Revision::newFromTitle( $this->getTitle() );
		}

		return $revision;
	}

}
