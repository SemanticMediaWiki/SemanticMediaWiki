<?php

namespace SMW\Tests\Utils\Mock;

use InvalidArgumentException;
use OutOfBoundsException;
use SMW\ObjectDictionary;
use SMW\Options;

/**
 * @codeCoverageIgnore
 *
 * MockObject builder provides methods that are being used by the mock repository
 * to define and create a mock object
 *
 * $title = new MockObjectBuilder()
 * $title->newObject( 'Foo', array(
 * 	'Bar' => ...
 * ) )
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MockObjectBuilder extends \PHPUnit_Framework_TestCase {

	/** @var ObjectDictionary */
	protected $configuration;

	/** @var MockObjectRepository */
	protected $repository = [];

	/**
	 * @since  1.9
	 *
	 * @param MockObjectRepository|null $repository
	 */
	public function __construct( MockObjectRepository $repository = null ) {

		if ( $repository === null ) {
			$repository = new CoreMockObjectRepository();
		}

		$this->registerRepository( $repository );
	}

	/**
	 * @since 1.9
	 *
	 * @param MockObjectRepository $repository
	 */
	public function registerRepository( MockObjectRepository $repository ) {
		$this->repository[] = $repository;
	}

	/**
	 * Helper method that stores configuration settings
	 *
	 * @since 1.9
	 *
	 * @param $objectName
	 * @param $objectArguments
	 *
	 * @return mixed
	 */
	public function newObject( $objectName, $objectArguments = [] ) {

		if ( !is_string( $objectName ) ) {
			throw new InvalidArgumentException( "Object name is not a string" );
		}

		if ( $objectArguments !== [] && !is_array( $objectArguments ) ) {
			throw new InvalidArgumentException( "Arguments are not an array type" );
		}

		$repository = $this->findRepositoryForObject( $objectName );

		if ( !$repository instanceof MockObjectRepository ) {
			throw new OutOfBoundsException( "{$objectName} method doesn't exists" );
		}

		$repository->registerBuilder( $this );
		$this->setupConfiguration( $objectArguments );

		return $repository->{$objectName}();
	}

	/**
	 * Returns invoked configuration keys
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getInvokedMethods() {
		return array_keys( $this->configuration->getOptions() );
	}

	/**
	 * Helper method that returns a random string
	 *
	 * @since 1.9
	 *
	 * @param $length
	 * @param $prefix identify a specific random string during testing
	 *
	 * @return string
	 */
	public function newRandomString( $length = 10, $prefix = null ) {
		return $prefix . ( $prefix ? '-' : '' ) . substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	/**
	 * Whether the configuration is known
	 *
	 * @since 1.9
	 *
	 * @param $key
	 *
	 * @return boolean
	 */
	public function hasValue( $key ) {
		return $this->configuration->has( $key );
	}

	/**
	 * Sets value
	 *
	 * @since 1.9
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed|null
	 */
	public function setValue( $key, $default = null ) {
		return $this->configuration->has( $key ) ? $this->configuration->get( $key ) : $default;
	}

	/**
	 * Determine callback function otherwise return simple value
	 *
	 * @since 1.9
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed|null
	 */
	public function setCallback( $key, $default = null ) {
		return is_callable( $this->setValue( $key ) ) ? $this->returnCallback( $this->setValue( $key ) ) : $this->returnValue( $this->setValue( $key, $default ) );
	}

	/**
	 * @since 1.9
	 *
	 * @param $objectName
	 *
	 * @return MockObjectRepository|null
	 */
	protected function findRepositoryForObject( $objectName ) {

		foreach ( $this->repository as $repository ) {
			if ( method_exists( $repository, $objectName ) ) {
				return $repository;
			}
		}

		return null;
	}

	/**
	 * @since 1.9
	 *
	 * @param $config
	 */
	protected function setupConfiguration( $config ) {

		$configuration = new Options( $config );

		if ( $this->configuration instanceof Options ) {
			return $this->configuration = new Options(
				array_merge( $this->configuration->getOptions(), $configuration->getOptions() )
			);
		}

		$this->configuration = $configuration;
	}

}
