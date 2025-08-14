<?php

namespace SMW;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use Parser;
use ParserOptions;
use RequestContext;
use SMW\MediaWiki\RevisionGuardAwareTrait;
use Title;
use User;

/**
 * Fetches the ParserOutput either by parsing an invoked text component,
 * re-parsing a text revision, or accessing the ContentHandler to generate a
 * ParserOutput object
 *
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
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

	/** @var RevisionRecord */
	protected $revision = null;

	/** @var array */
	protected $errors = [];

	/**
	 * @var bool
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
	public function setRevision( ?RevisionRecord $revision = null ) {
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
	 * Parses the page content or provided text, with a temporary hack to avoid
 	* accessing Parser::$mOutput before initialization on MW 1.43.x.
 	*
 	* @param string|null $text  Optional wikitext to parse instead of page text.
 	* @param bool        $clear Whether to clear the parser state before parsing.
 	* @return ParserOutput|null Returns ParserOutput on success, null on hack fallback.
 	*/
	public function parse( ?string $text = null, bool $clear = true ) {
    	// If explicit text is provided, attempt to parse it directly.
    	if ( $text !== null ) {
        	// Temporary hack: check that the Parser::$mOutput property is initialized
        	$parser = $this->getParser();
        	if ( $parser instanceof \MediaWiki\Parser\Parser ) {
            	$ref = new \ReflectionClass( \MediaWiki\Parser\Parser::class );
            	$prop = $ref->getProperty( 'mOutput' );
            	if ( !$prop->isInitialized( $parser ) ) {
                	// Skip parsing to avoid fatal error; return null fallback
                	wfDebugLog( 'SemanticMediaWiki', 'Parser::$mOutput uninitialized, skipping parseText()' );
                	return null;
            	}
        	}
        	return $this->parseText( $text, $clear );
    	}

    	// No text override: fetch cached or fresh content.
    	// Temporary hack also applies here if fetchFromContent() triggers parse()
    	$parser = $this->getParser();
    	if ( $parser instanceof \MediaWiki\Parser\Parser ) {
        	$ref = new \ReflectionClass( \MediaWiki\Parser\Parser::class );
        	$prop = $ref->getProperty( 'mOutput' );
        	if ( !$prop->isInitialized( $parser ) ) {
            	wfDebugLog( 'SemanticMediaWiki', 'Parser::$mOutput uninitialized, skipping fetchFromContent()' );
	            return null;
    	    }
    	}

    	return $this->fetchFromContent();
	}


	private function parseText( ?string $text, bool $clear ) {
		$this->parserOutput = $this->parser->parse(
			$text,
			$this->getTitle(),
			$this->makeParserOptions(),
			true,
			$clear
		);

		return $this;
	}

	private function fetchFromContent() {
		if ( $this->getRevision() === null ) {
			return $this->msgForNullRevision();
		}

		$revision = $this->getRevision();
		$content = $revision->getContent( SlotRecord::MAIN, RevisionRecord::RAW );

		if ( !$content ) {
			$mainSlot = $revision->getSlot( SlotRecord::MAIN, RevisionRecord::RAW );
			$contentHandlerFactory = MediaWikiServices::getInstance()->getContentHandlerFactory();
			$handler = $contentHandlerFactory->getContentHandler( $mainSlot->getModel() );
			$content = $handler->makeEmptyContent();
		}

		// Avoid "The content model 'xyz' is not registered on this wiki."
		try {
			$services = MediaWikiServices::getInstance();
			// MW 1.42+
			if ( version_compare( MW_VERSION, '1.42', '<' ) ) {
				$revision = $revision->getId();
			}
			$contentRenderer = $services->getContentRenderer();
			$this->parserOutput = $contentRenderer->getParserOutput(
				$content,
				$this->getTitle(),
				$revision
			);
		} catch ( \MWUnknownContentModelException $e ) {
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
			$identity = $this->getRevision()->getUser();
			if ( $identity ) {
				$user = User::newFromIdentity( $identity );
			}
		}

		$user = $user ?? RequestContext::getMain()->getUser();
		$parserOptions = new ParserOptions( $user );

		// Use the InterfaceMessage marker to skip InTextAnnotationParser
		// processing
		$parserOptions->setInterfaceMessage( $this->skipInTextAnnotationParser );

		return $parserOptions;
	}

	private function getRevision() {
		if ( $this->revision instanceof RevisionRecord ) {
			return $this->revision;
		}

		$this->revision = $this->revisionGuard->getRevision(
			$this->getTitle(),
			$this->revision
		);

		return $this->revision;
	}

}
