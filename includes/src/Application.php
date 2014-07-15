<?php

namespace SMW;

use SMW\MediaWiki\Jobs\JobFactory;
use SMW\Annotator\PropertyAnnotatorFactory;

use ParserOutput;
use Title;

/**
 * Application instances access for internal and external use
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class Application {

	/**
	 * @var Application
	 */
	private static $instance = null;

	/**
	 * @var DependencyBuilder
	 */
	private $builder = null;

	/**
	 * @since 2.0
	 */
	protected function __construct( DependencyBuilder $builder = null ) {
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
	 * @return JobFactory
	 */
	public function newJobFactory() {
		return new JobFactory();
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
	 * @return ParserData
	 */
	public function newParserData( Title $title, ParserOutput $parserOutput ) {
		return $this->builder->newObject( 'ParserData', array(
			'Title'        => $title,
			'ParserOutput' => $parserOutput
		) );
	}

	private static function registerBuilder( DependencyBuilder $builder = null ) {

		if ( $builder === null ) {
			$builder = new SimpleDependencyBuilder( new SharedDependencyContainer() );
		}

		return $builder;
	}

}
