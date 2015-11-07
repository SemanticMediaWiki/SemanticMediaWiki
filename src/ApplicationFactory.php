<?php

namespace SMW;

use Parser;
use ParserOutput;
use SMW\Factbox\FactboxFactory;
use SMW\MediaWiki\Jobs\JobFactory;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\TitleCreator;
use SMW\Query\ProfileAnnotator\QueryProfileAnnotatorFactory;
use SMW\Maintenance\MaintenanceFactory;
use SMW\CacheFactory;
use SMWQueryParser as QueryParser;
use Title;
use Onoi\CallbackContainer\CallbackLoader;
use Onoi\CallbackContainer\DeferredCallbackLoader;

/**
 * Application instances access for internal and external use
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ApplicationFactory {

	/**
	 * @var ApplicationFactory
	 */
	private static $instance = null;

	/**
	 * @var CallbackLoader
	 */
	private $callbackLoader = null;

	/**
	 * @since 2.0
	 */
	public function __construct( CallbackLoader $callbackLoader = null ) {
		$this->callbackLoader = $callbackLoader;
	}

	/**
	 * This method returns the global instance of the application factory.
	 *
	 * Reliance on global state is needed at entry points into SMW such as
	 * hook handlers, special pages and jobs, since there we tend to not
	 * have control over the object lifecycle. Pragmatically we might also
	 * want to use this when refactoring legacy code that already has the
	 * global state dependency. For new code very special justification is
	 * required to rely on global state.
	 *
	 * @since 2.0
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self( self::registerBuilder() );
		}

		return self::$instance;
	}

	/**
	 * @since 2.0
	 */
	public static function clear() {

		if ( self::$instance !== null ) {
			self::$instance->getSettings()->clear();
		}

		self::$instance = null;
	}

	/**
	 * @since 2.0
	 *
	 * @param string $objectName
	 * @param callable|array $objectSignature
	 */
	public function registerObject( $objectName, $objectSignature ) {
		$this->callbackLoader->registerObject( $objectName, $objectSignature );
	}

	/**
	 * @since 2.0
	 *
	 * @return SerializerFactory
	 */
	public function newSerializerFactory() {
		return new SerializerFactory();
	}

	/**
	 * @since 2.0
	 *
	 * @return FactboxFactory
	 */
	public function newFactboxFactory() {
		return $this->callbackLoader->load( 'FactboxFactory' );
	}

	/**
	 * @since 2.0
	 *
	 * @return PropertyAnnotatorFactory
	 */
	public function newPropertyAnnotatorFactory() {
		return new PropertyAnnotatorFactory();
	}

	/**
	 * @since 2.0
	 *
	 * @return JobFactory
	 */
	public function newJobFactory() {
		return $this->callbackLoader->load( 'JobFactory' );
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return ParserFunctionFactory
	 */
	public function newParserFunctionFactory( Parser $parser ) {
		return new ParserFunctionFactory( $parser );
	}

	/**
	 * @since 2.1
	 *
	 * @return QueryProfileAnnotatorFactory
	 */
	public function newQueryProfileAnnotatorFactory() {
		return new QueryProfileAnnotatorFactory();
	}

	/**
	 * @since 2.2
	 *
	 * @return MaintenanceFactory
	 */
	public function newMaintenanceFactory() {
		return new MaintenanceFactory();
	}

	/**
	 * @since 2.2
	 *
	 * @return CacheFactory
	 */
	public function newCacheFactory() {
		return new CacheFactory( $this->getSettings()->get( 'smwgCacheType' ) );
	}

	/**
	 * @since 2.0
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->callbackLoader->singleton( 'Store' );
	}

	/**
	 * @since 2.0
	 *
	 * @return Settings
	 */
	public function getSettings() {
		return $this->callbackLoader->singleton( 'Settings' );
	}

	/**
	 * @since 2.0
	 *
	 * @return TitleCreator
	 */
	public function newTitleCreator() {
		return $this->callbackLoader->load( 'TitleCreator', $this->newPageCreator() );
	}

	/**
	 * @since 2.0
	 *
	 * @return PageCreator
	 */
	public function newPageCreator() {
		return $this->callbackLoader->load( 'PageCreator' );
	}

	/**
	 * @since 2.0
	 *
	 * @return Cache
	 */
	public function getCache() {
		return $this->callbackLoader->singleton( 'Cache' );
	}

	/**
	 * @since 2.0
	 *
	 * @return InTextAnnotationParser
	 */
	public function newInTextAnnotationParser( ParserData $parserData ) {

		$mwCollaboratorFactory = $this->newMwCollaboratorFactory();

		$inTextAnnotationParser = new InTextAnnotationParser(
			$parserData,
			$mwCollaboratorFactory->newMagicWordsFinder(),
			$mwCollaboratorFactory->newRedirectTargetFinder()
		);

		$inTextAnnotationParser->setStrictModeState(
			$this->getSettings()->get( 'smwgEnabledInTextAnnotationParserStrictMode' )
		);

		return $inTextAnnotationParser;
	}

	/**
	 * @since 2.0
	 *
	 * @return ParserData
	 */
	public function newParserData( Title $title, ParserOutput $parserOutput ) {
		return $this->callbackLoader->load( 'ParserData', $title, $parserOutput );
	}

	/**
	 * @since 2.0
	 *
	 * @return ContentParser
	 */
	public function newContentParser( Title $title ) {
		return $this->callbackLoader->load( 'ContentParser', $title );
	}

	/**
	 * @since 2.1
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return StoreUpdater
	 */
	public function newStoreUpdater( SemanticData $semanticData ) {
		return new StoreUpdater( $this->getStore(), $semanticData );
	}

	/**
	 * @since 2.1
	 *
	 * @return MwCollaboratorFactory
	 */
	public function newMwCollaboratorFactory() {
		return new MwCollaboratorFactory( $this );
	}

	/**
	 * @since 2.1
	 *
	 * @return NamespaceExaminer
	 */
	public function getNamespaceExaminer() {
		return $this->callbackLoader->load( 'NamespaceExaminer' );
	}

	/**
	 * @since 2.1
	 *
	 * @return QueryParser
	 */
	public function newQueryParser() {
		return new QueryParser();
	}

	private static function registerBuilder( CallbackLoader $callbackLoader = null ) {

		if ( $callbackLoader === null ) {
			$callbackLoader = new DeferredCallbackLoader( new SharedCallbackContainer() );
		}

		return $callbackLoader;
	}

}
