<?php

namespace SMW\Test;

use SMW\ObjectDictionary;
use SMW\SimpleDictionary;
use SMWDataItem;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * MockObject builder
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * MockObject builder provides methods that are being used by the mock repository
 * to define and create a mock object
 *
 * $title = new MockObjectBuilder()
 * $title->newObject( 'Foo', array(
 * 	'Bar' => ...
 * ) )
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @codeCoverageIgnore
 */
class MockObjectBuilder extends \PHPUnit_Framework_TestCase {

	/** @var ObjectDictionary */
	protected $configuration;

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
	public function newObject( $objectName, $objectArguments = array() ) {

		if ( !is_string( $objectName ) ) {
			throw new InvalidArgumentException( "Object name is not a string" );
		}

		if ( $objectArguments !== array() && !is_array( $objectArguments ) ) {
			throw new InvalidArgumentException( "Arguments are not an array type" );
		}

		$repository = new MockObjectRepository( $this );

		if ( !method_exists( $repository, $objectName ) ) {
			throw new OutOfBoundsException( "{$objectName} method doesn't exists" );
		}

		$this->setupConfiguration( $objectArguments );

		return $repository->{$objectName}();
	}

	/**
	 * Helper method that stores configuration settings
	 *
	 * @since 1.9
	 *
	 * @param $config
	 */
	protected function setupConfiguration( $config ) {

		$configuration = new SimpleDictionary( $config );

		if ( $this->configuration instanceof SimpleDictionary ) {
			return $this->configuration->merge( $configuration->toArray() );
		}

		$this->configuration = $configuration;
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

}
