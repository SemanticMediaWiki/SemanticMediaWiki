<?php

namespace SMW;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Implements the DependencyBuilder
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Basic implementation of the DependencyBuilder interface to enable access to
 * DependencyContainer objects and other invoked arguments
 *
 * @ingroup DependencyBuilder
 */
class SimpleDependencyBuilder implements DependencyBuilder {

	/** @var DependencyContainer */
	protected $dependencyContainer = null;

	/**
	 * @note In case no DependencyContainer has been injected during construction
	 * an empty container is set as default to enable registration without the need
	 * to rely on constructor injection.
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder() or
	 *  $builder = new SimpleDependencyBuilder( new EmptyDependencyContainer() )
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param DependencyContainer|null $dependencyContainer
	 */
	public function __construct( DependencyContainer $dependencyContainer = null ) {

		$this->dependencyContainer = $dependencyContainer;

		if ( $this->dependencyContainer === null ) {
			$this->dependencyContainer = new EmptyDependencyContainer();
		}

	}

	/**
	 * Registers a DependencyContainer
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder()
	 *
	 *  // Setter injection
	 *  $builder->registerContainer( new GenericDependencyContainer() )
	 *
	 *  // Register multiple container
	 *  $builder = new SimpleDependencyBuilder()
	 *  $builder->registerContainer( new GenericDependencyContainer() )
	 *  $builder->registerContainer( new AnotherDependencyContainer() )
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param DependencyContainer $container
	 */
	public function registerContainer( DependencyContainer $container ) {
		$this->dependencyContainer->merge( $container->toArray() );
	}

	/**
	 * @see DependencyBuilder::getArgument
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder( new EmptyDependencyContainer() );
	 *  $builder->getContainer() returns EmptyDependencyContainer
	 *
	 *  // Register additional objects during runtime
	 *  $builder->getContainer()->registerObject( 'Title', new Title() ) or
	 *  $builder->getContainer()->registerObject( 'DIWikiPage', function ( DependencyBuilder $builder ) {
	 *  	return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
	 *  } );
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @return DependencyContainer
	 */
	public function getContainer() {
		return $this->dependencyContainer;
	}

	/**
	 * Creates a new object
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder( ... )
	 *
	 *  $diWikiPage = $builder->newObject( 'DIWikiPage', array( $title ) ) or
	 *  $diWikiPage = $builder->addArgument( 'Title', $title )->newObject( 'DIWikiPage' );
	 * @endcode
	 *
	 * @since  1.9
	 *
	 * @param  string $objectName
	 *
	 * @return mixed
	 */
	public function newObject( $objectName ) {
		return $this->setArguments( func_get_args() )->build( $objectName );
	}

	/**
	 * Build dynamic entities via magic method __get
	 *
	 * @par Example:
	 * @code
	 *  $builder = new SimpleDependencyBuilder( ... )
	 *
	 *  // Register object using __Set
	 *  $builder->getContainer()->title = new Title()
	 *
	 *  // Retrieve object using __get
	 *  $builder->title returns Title object
	 * @endcode
	 *
	 * @param string $objectName
	 */
	public function __get( $objectName ) {
		return $this->build( $objectName );
	}

	/**
	 * @see DependencyBuilder::getArgument
	 *
	 * @note Arguments are being preceded by a "arg_" to distingiush those
	 * objects internally from regisered DI objects. The handling is only
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

		if ( !( $this->dependencyContainer->has( 'arg_' . $key ) ) ) {
			throw new OutOfBoundsException( "'{$key}' argument is invalid or unknown." );
		}

		return $this->dependencyContainer->get( 'arg_' . $key );
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
	 * Auto registeration for arguments that were used during object invocation
	 *
	 * @note Only the first array contains arguments relevant to the object
	 * creation
	 *
	 * @since  1.9
	 *
	 * @param array $args
	 *
	 * @return DependencyBuilder
	 */
	protected function setArguments( array $args ) {

		if ( isset( $args[1] ) && is_array( $args[1] ) ) {
			foreach ( $args[1] as $key => $value ) {
				$this->addArgument( get_class( $value ), $value );
			}
		}

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
	 * @throws InvalidArgumentException
	 */
	protected function build( $objectName ) {

		if ( !is_string( $objectName ) ) {
			throw new InvalidArgumentException( 'Argument is not a string' );
		}

		if ( !$this->dependencyContainer->has( $objectName ) ) {
			throw new OutOfBoundsException( "{$objectName} is not registered" );
		}

		$object = $this->dependencyContainer->get( $objectName );

		return is_callable( $object ) ? $object( $this ) : $object;
	}

}
