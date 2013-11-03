<?php

namespace SMW;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Implements a basic DependencyBuilder
 *
 * @par Example:
 * For a more exhaustive description and examples on how to "work with
 * a DependencyBuilder/Container", see /docs/dic.md
 *
 * @ingroup DependencyBuilder
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SimpleDependencyBuilder implements DependencyBuilder {

	/** @var DependencyContainer */
	protected $dependencyContainer = null;

	/** @var integer */
	protected $objectScope = null;

	/**
	 * Iteration counter which indicates the level of iteration over resolving an
	 * object graph for a single definition. It is expected that a certain level
	 * of recursion is necessary to fully expand an object but it is in anticipation
	 * that no definition will have a depth greater than $recursionDepth
	 *
	 * @var integer
	 */
	protected $recursionLevel = 0;

	/**
	 * Specifies a max depth (or threshold) for a dependency graph. In case a build
	 * will overstep this limit it is assumed that the builder is caught in an
	 * infinite loop due to a self-reference (circular reference) within its
	 * object definition
	 *
	 * @var integer
	 */
	protected $recursionDepth = 10;

	/**
	 * @note In case no DependencyContainer has been injected during construction
	 * an empty container is set as default to enable registration without the need
	 * to rely on constructor injection.
	 *
	 * @since  1.9
	 *
	 * @param DependencyContainer|null $dependencyContainer
	 */
	public function __construct( DependencyContainer $dependencyContainer = null ) {

		$this->dependencyContainer = $dependencyContainer;

		if ( $this->dependencyContainer === null ) {
			$this->dependencyContainer = new NullDependencyContainer();
		}

	}

	/**
	 * Register a DependencyContainer
	 *
	 * @since  1.9
	 *
	 * @param DependencyContainer $container
	 */
	public function registerContainer( DependencyContainer $container ) {

		$this->dependencyContainer->loadAllDefinitions();
		$container->loadAllDefinitions();

		$this->dependencyContainer->merge( $container->toArray() );
	}

	/**
	 * @see DependencyBuilder::getContainer
	 *
	 * @since  1.9
	 *
	 * @return DependencyContainer
	 */
	public function getContainer() {
		return $this->dependencyContainer;
	}

	/**
	 * Create a new object
	 *
	 * @note When adding arguments it is preferable to use type hinting even
	 * though auto type recognition is supported but using mock objects during
	 * testing will cause objects being recognized with their mock name instead
	 * of the original mocked entity
	 *
	 * @since  1.9
	 *
	 * @param  string $objectName
	 * @param  array|null $objectArguments
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function newObject( $objectName, $objectArguments = null ) {

		if ( !is_string( $objectName ) ) {
			throw new InvalidArgumentException( 'Object name is not a string' );
		}

		if ( $objectArguments !== null ) {

			if ( !is_array( $objectArguments ) ) {
				throw new InvalidArgumentException( "Arguments are not an array type" );
			}

			foreach ( $objectArguments as $key => $value ) {
				$this->addArgument( is_string( $key ) ? $key : get_class( $value ), $value );
			}
		}

		return $this->build( $objectName );
	}

	/**
	 * Create a new object using the magic __call method
	 *
	 * @param string $objectName
	 * @param array|null $objectArguments
	 *
	 * @return mixed
	 */
	public function __call( $objectName, $objectArguments = null ) {

		if ( isset( $objectArguments[0] ) && is_array( $objectArguments[0] ) ) {
			$objectArguments = $objectArguments[0];
		}

		return $this->newObject( $objectName, $objectArguments );
	}

	/**
	 * @see DependencyBuilder::getArgument
	 *
	 * @note Arguments are being preceded by a "arg_" to distinguish those
	 * objects internally from registered DI objects. The handling is only
	 * relevant for those internal functions and hidden from the public
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws OutOfBoundsException
	 */
	public function getArgument( $key ) {

		if ( !( $this->hasArgument( $key ) ) ) {
			throw new OutOfBoundsException( "'{$key}' argument is invalid or unknown." );
		}

		return $this->dependencyContainer->get( 'arg_' . $key );
	}

	/**
	 * @see DependencyBuilder::hasArgument
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function hasArgument( $key ) {

		if ( !is_string( $key ) ) {
			throw new InvalidArgumentException( "Argument is not a string" );
		}

		return $this->dependencyContainer->has( 'arg_' . $key );
	}

	/**
	 * @see DependencyBuilder::addArgument
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return DependencyBuilder
	 * @throws InvalidArgumentException
	 */
	public function addArgument( $key, $value ) {

		if ( !is_string( $key ) ) {
			throw new InvalidArgumentException( "Argument is not a string" );
		}

		$this->dependencyContainer->set( 'arg_' . $key, $value );

		return $this;
	}

	/**
	 * @see DependencyBuilder::setScope
	 *
	 * @since  1.9
	 *
	 * @param $objectScope
	 *
	 * @return DependencyBuilder
	 */
	public function setScope( $objectScope ) {
		$this->objectScope = $objectScope;
		return $this;
	}

	/**
	 * Builds an object from a registered specification
	 *
	 * @since  1.9
	 *
	 * @param $object
	 *
	 * @return mixed
	 * @throws OutOfBoundsException
	 */
	protected function build( $objectName ) {

		if ( !$this->dependencyContainer->has( $objectName ) ) {

			if ( !$this->searchObjectByName( $objectName ) ) {
				throw new OutOfBoundsException( "{$objectName} is not registered or available as service object" );
			};

		}

		if ( $this->recursionLevel++ > $this->recursionDepth ) {
			throw new OutOfBoundsException( "Possible circular reference for '{$objectName}' has been detected" );
		}

		list( $objectSignature, $objectScope ) = $this->dependencyContainer->get( $objectName );

		if ( is_string( $objectSignature ) && class_exists( $objectSignature ) ) {
			$objectSignature = new $objectSignature;
		}

		if ( $objectSignature instanceOf DependencyObject ) {
			$objectSignature = $objectSignature->retrieveDefinition( $this );
		}

		return $this->load( $objectName, $objectSignature, $objectScope );
	}

	/**
	 * Initializes an object in correspondence with its scope and specification
	 *
	 * @note An object scope invoked during the build process has priority
	 * over the original scope definition
	 *
	 * @param string $objectName
	 * @param mixed $objectSignature
	 * @param integer $objectScope
	 *
	 * @return mixed
	 */
	private function load( $objectName, $objectSignature, $objectScope ) {

		if ( $this->objectScope !== null ) {
			$objectScope = $this->objectScope;
		}

		if ( $objectScope === DependencyObject::SCOPE_SINGLETON ) {
			$objectSignature = $this->singleton( $objectName, $objectSignature );
		}

		$instance = is_callable( $objectSignature ) ? $objectSignature( $this ) : $objectSignature;

		$this->objectScope = null;
		$this->recursionLevel--;

		return $instance;
	}

	/**
	 * Builds singleton instance
	 *
	 * @note A static context within a closure is kept static for its lifetime
	 * therefore any repeated call to the same instance within the same request
	 * will return the static context it was first initialized
	 *
	 * @note Objects with a singleton scope are internally stored and preceded by
	 * a 'sing_' as object identifier
	 *
	 * @param mixed $objectSignature
	 *
	 * @return Closure
	 */
	private function singleton( $objectName, $objectSignature ) {

		$objectName = 'sing_' . $objectName;

		if ( !$this->dependencyContainer->has( $objectName ) ) {

			// Resolves an object and uses the result for further processing
			$object = is_callable( $objectSignature ) ? $objectSignature( $this ) : $objectSignature;

			// Binds static context
			$singleton = function() use ( $object ) {
				static $singleton;
				return $singleton = $singleton === null ? $object : $singleton;
			};

			$this->dependencyContainer->set( $objectName, $singleton );
		} else {
			$singleton = $this->dependencyContainer->get( $objectName );
		}

		return $singleton;
	}

	/**
	 * @since  1.9
	 *
	 * @return boolean
	 */
	private function searchObjectByName( $objectName ) {

		$objectCatalog = $this->dependencyContainer->loadAllDefinitions();

		if ( isset( $objectCatalog[$objectName] ) ) {

			$this->dependencyContainer->registerObject( $objectName, $objectCatalog[$objectName] );
			return true;

		}

		return false;
	}

}
