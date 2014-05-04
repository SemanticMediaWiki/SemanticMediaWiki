<?php

namespace SMW\DIC;

use SMW\ContextResource;
use SMW\ExtensionContext;
use SMW\SemanticData;
use SMW\ParserData;
use SMW\InTextAnnotationParser;
use SMW\MediaWiki\MagicWordFinder;

use Title;
use ParserOutput;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class ObjectFactory {

	protected static $instance = null;

	protected $context = null;

	protected function __construct( ContextResource $context ) {
		$this->context = $context;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return ObjectFactory
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self( new ExtensionContext() );
		}

		return self::$instance;
	}

	/**
	 * @since 1.9.3
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return ContextResource $context
	 */
	public function invokeContext( ContextResource $context ) {
		$this->context = $context;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param string $objectName
	 * @param Closure|array $objectSignature
	 */
	public function registerObject( $objectName, $objectSignature ) {
		$this->context->getDependencyBuilder()->getContainer()->registerObject( $objectName, $objectSignature );
	}

	/**
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public function getSettings() {
		return $this->context->getDependencyBuilder()->newObject( 'Settings' );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return ParserData
	 */
	public function newByParserData( Title $title, ParserOutput $parserOutput ) {
		return $this->context->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $title,
			'ParserOutput' => $parserOutput
		) );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return ParserData
	 */
	public function newInTextAnnotationParser( ParserData $parserData ) {
		return new InTextAnnotationParser(
			$parserData,
			$this->newMagicWordFinder( $parserData->getOutput() )
		);
	}

	/**
	 * @since 1.9.3
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @return MagicWordFinder
	 */
	public function newMagicWordFinder( ParserOutput $parserOutput ) {
		return new MagicWordFinder( $parserOutput );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param SemanticData $semanticData
	 * @param Title|null $redirectTarget
	 *
	 * @return RedirectPropertyAnnotator
	 */
	public function newRedirectPropertyAnnotator( SemanticData $semanticData, Title $redirectTarget = null ) {
		return $this->context->getDependencyBuilder()->newObject( 'RedirectPropertyAnnotator', array(
			'SemanticData' => $semanticData,
			'Title'        => $redirectTarget
		) );
	}

}