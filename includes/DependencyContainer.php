<?php

namespace SMW;

/**
 * Provides objects for dependcy injection
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface specifying a dependency object
 *
 * @ingroup DependencyContainer
 */
interface DependencyObject {

	/**
	 * Register a dependency object
	 *
	 * @since  1.9
	 */
	public function registerObject( $objectName, $objectSignature );

}

/**
 * Interface specifying a dependency container
 *
 * @ingroup DependencyContainer
 */
interface DependencyContainer extends DependencyObject, Accessible, Changeable, Combinable {}

/**
 * Provides a DependencyContainer base class
 *
 * @ingroup DependencyContainer
 */
abstract class DependencyContainerBase extends ObjectStorage implements DependencyContainer {

	/**
	 * @see ObjectStorage::contains
	 *
	 * @since  1.9
	 */
	public function has( $key ) {
		return $this->contains( $key );
	}

	/**
	 * @see ObjectStorage::lookup
	 *
	 * @since  1.9
	 */
	public function get( $key ) {
		return $this->lookup( $key );
	}

	/**
	 * @see ObjectStorage::attach
	 *
	 * @since  1.9
	 */
	public function set( $key, $value ) {
		return $this->attach( $key, $value );
	}

	/**
	 * @see ObjectStorage::detach
	 *
	 * @since  1.9
	 */
	public function remove( $key ) {
		return $this->detach( $key );
	}

	/**
	 * Returns storage array
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->storage;
	}

	/**
	 * Merges elements of one or more arrays together
	 *
	 * @since 1.9
	 *
	 * @param array $mergeable
	 *
	 * @return HashArray
	 */
	public function merge( array $mergeable ) {
		$this->storage = array_merge( $this->storage, $mergeable );
	}

	/**
	 * Register an object via magic method __set
	 *
	 * @par Example:
	 * @code
	 *  $container = new EmptyDependencyContainer()
	 *
	 *  // Eager loading (do everything when asked)
	 *  $container->title = new Title() or
	 *
	 *  // Lazy loading (only do an instanitation when required)
	 *  $container->diWikiPage = function ( DependencyBuilder $builder ) {
	 *    return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
	 *  } );
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $objectName, $objectSignature ) {
		$this->set( $objectName, $objectSignature );
	}

	/**
	 * Register an object
	 *
	 * @par Example:
	 * @code
	 *  $container = new EmptyDependencyContainer()
	 *
	 *  // Eager loading (do everything when asked)
	 *  $container->registerObject( 'Title', new Title() ) or
	 *
	 *  // Lazy loading (only do an instanitation when required)
	 *  $container->registerObject( 'DIWikiPage', function ( DependencyBuilder $builder ) {
	 *    return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
	 *  } );
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param string $objectName
	 * @param mixed $signature
	 */
	public function registerObject( $objectName, $objectSignature ) {
		$this->set( $objectName, $objectSignature );
	}

}

/**
 * Implementation of an empty DependencyContainer entity
 *
 * @ingroup DependencyContainer
 */
class EmptyDependencyContainer extends DependencyContainerBase {}

/**
 * Implementation of a general purpose objects DependencyContainer
 *
 * @ingroup DependencyContainer
 */
class CommonDependencyContainer extends DependencyContainerBase {

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
