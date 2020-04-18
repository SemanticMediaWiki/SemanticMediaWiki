<?php

namespace SMW;

use Parser;
use ParserOptions;
use Revision;
use Title;
use User;
use SMW\MediaWiki\RevisionGuard;
use SMW\MediaWiki\RevisionGuardAwareTrait;

/**
 * Fetches the ParserOutput either by parsing an invoked text component,
 * re-parsing a text revision, or accessing the ContentHandler to generate a
 * ParserOutput object
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ContentParser {

	use RevisionGuardAwareTrait;

	/** @var Title */
	protected $title;

	/** @var Parser */
	protected $parser = null;

	/** @var ParserOutput */
	protected $parserOutput = null;

	/** @var Revision */
	protected $revision = null;

	/** @var array */
	protected $errors = [];

	/**
	 * @var boolean
	 */
	private $skipInTextAnnotationParser = false;

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param Parser $parser
	 */
	public function __construct( Title $title, Parser $parser ) {
		$this->title  = $title;
		$this->parser = $parser;
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

	/**
	 * @since 1.9
	 */
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

		return $this->fetchFromContent();
	}

	private function parseText( $text ) {

		$this->parserOutput = $this->parser->parse(
			$text,
			$this->getTitle(),
			$this->makeParserOptions()
		);

		return $this;
	}

	private function fetchFromContent() {

		if ( $this->getRevision() === null ) {
			return $this->msgForNullRevision();
		}

		$revision = $this->getRevision();
		$content = $revision->getContent( Revision::RAW );

		if ( !$content ) {
			$content = $revision->getContentHandler()->makeEmptyContent();
		}

		// Avoid "The content model 'xyz' is not registered on this wiki."
		try {
			$this->parserOutput = $content->getParserOutput(
				$this->getTitle(),
				$revision->getId(),
				null,
				true
			);
		} catch( \MWUnknownContentModelException $e ) {
			$this->parserOutput = null;
		}

		return $this;
	}

	private function msgForNullRevision( $fname = __METHOD__ ) {
		$this->errors = [ $fname . " No revision available for {$this->getTitle()->getPrefixedDBkey()}" ];
		return $this;
	}

	private function makeParserOptions() {

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

	private function getRevision() {

		if ( $this->revision instanceof Revision ) {
			return $this->revision;
		}

		$this->revision = $this->revisionGuard->getRevision(
			$this->getTitle(),
			$this->revision
		);

		return $this->revision;
	}

}
