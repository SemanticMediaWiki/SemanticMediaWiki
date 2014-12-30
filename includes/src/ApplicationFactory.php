<?php

namespace SMW;

use SMW\MediaWiki\Jobs\JobFactory;
use SMW\Annotator\PropertyAnnotatorFactory;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\Factbox\FactboxBuilder;
use SMW\Query\Profiler\QueryProfilerFactory;

use SMWQueryParser as QueryParser;

use ParserOutput;
use Parser;
use Language;
use Title;

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
	 * @var DependencyBuilder
	 */
	private $builder = null;

	/**
	 * @since 2.0
	 */
	public function __construct( DependencyBuilder $builder = null ) {
		$this->builder = $builder;
	}

	/**
	 * @since 2.0
	 *
	 * @return Application
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
	 * @param Closure|array $objectSignature
	 *
	 * @return Application
	 */
	public function registerObject( $objectName, $objectSignature ) {
		$this->builder->getContainer()->registerObject( $objectName, $objectSignature );
		return $this;
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
	 * @return FactboxBuilder
	 */
	public function newFactboxBuilder() {
		return new FactboxBuilder();
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
		return  $this->builder->newObject( 'JobFactory' );
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
	 * @return QueryProfilerFactory
	 */
	public function newQueryProfilerFactory() {
		return new QueryProfilerFactory();
	}

	/**
	 * @since 2.0
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->builder->newObject( 'Store' );
	}

	/**
	 * @since 2.0
	 *
	 * @return Settings
	 */
	public function getSettings() {
		return $this->builder->newObject( 'Settings' );
	}

	/**
	 * @since 2.0
	 *
	 * @return TitleCreator
	 */
	public function newTitleCreator() {
		return $this->builder->newObject( 'TitleCreator' );
	}

	/**
	 * @since 2.0
	 *
	 * @return PageCreator
	 */
	public function newPageCreator() {
		return $this->builder->newObject( 'PageCreator' );
	}

	/**
	 * @since 2.0
	 *
	 * @return CacheHandler
	 */
	public function getCache() {
		return $this->builder->newObject( 'CacheHandler' );
	}

	/**
	 * @since 2.0
	 *
	 * @return InTextAnnotationParser
	 */
	public function newInTextAnnotationParser( ParserData $parserData ) {

		$mwCollaboratorFactory = $this->newMwCollaboratorFactory();

		return new InTextAnnotationParser(
			$parserData,
			$mwCollaboratorFactory->newMagicWordFinder(),
			$mwCollaboratorFactory->newRedirectTargetFinder()
		);
	}

	/**
	 * @since 2.0
	 *
	 * @return ParserData
	 */
	public function newParserData( Title $title, ParserOutput $parserOutput ) {
		return $this->builder->newObject( 'ParserData', array(
			'Title'        => $title,
			'ParserOutput' => $parserOutput
		) );
	}

	/**
	 * @since 2.0
	 *
	 * @return ContentParser
	 */
	public function newContentParser( Title $title ) {
		return $this->builder->newObject( 'ContentParser', array(
			'Title' => $title
		) );
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
		return NamespaceExaminer::newFromArray( $this->getSettings()->get( 'smwgNamespacesWithSemanticLinks' ) );
	}

	/**
	 * @since 2.1
	 *
	 * @return QueryParser
	 */
	public function newQueryParser() {
		return new QueryParser();
	}

	private static function registerBuilder( DependencyBuilder $builder = null ) {

		if ( $builder === null ) {
			$builder = new SimpleDependencyBuilder( new SharedDependencyContainer() );
		}

		return $builder;
	}

}
