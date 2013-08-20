<?php

namespace SMW;

/**
 * Extends the BaseDependencyContainer
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Extends the BaseDependencyContainer to provide general purpose dependecy
 * object definitions
 *
 * @ingroup DependencyContainer
 */
class SharedDependencyContainer extends BaseDependencyContainer {

	/**
	 * @since  1.9
	 */
	public function __construct() {
		$this->load();
	}

	/**
	 * Load pre-existing object definitions
	 *
	 * @since  1.9
	 */
	public function load() {

		$this->registerObject( 'Settings', function () {
			return Settings::newFromGlobals();
		} );

		$this->registerObject( 'Store', function ( DependencyBuilder $builder ) {
			return StoreFactory::getStore( $builder->newObject( 'Settings' )->get( 'smwgDefaultStore' ) );
		} );

		$this->registerObject( 'CacheHandler', function ( DependencyBuilder $builder ) {
			return CacheHandler::newFromId( $builder->newObject( 'Settings' )->get( 'smwgCacheType' ) );
		} );

		$this->registerObject( 'ParserData', function ( DependencyBuilder $builder ) {
			return new ParserData(
				$builder->getArgument( 'Title' ),
				$builder->getArgument( 'ParserOutput' )
			);
		} );

		// $this->set( 'FactboxPresenter', function ( DependencyBuilder $builder ) {
		//	$outputPage = $builder->getArgument( 'OutputPage' );
		//	return new FactboxPresenter( $outputPage, $builder->newObject( 'Settings' ) );
		// } );

		// $this->set( 'Factbox', function ( DependencyBuilder $builder ) {
		//	return new Factbox(
		//		$builder->newObject( 'Store' ),
		//		$builder->getArgument( 'SMW\ParserData' ),
		//		$builder->getArgument( 'SMW\Settings' ),
		//		$builder->getArgument( 'RequestContext' )
		//	);
		// } );

	}

}
