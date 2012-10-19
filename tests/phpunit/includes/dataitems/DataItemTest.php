<?php

namespace SMW\Tests;

/**
 * Base class for SMW\DataItem tests.
 *
 * @file
 * @since 1.8
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWDataItems
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class DataItemTest extends \MediaWikiTestCase {

	/**
	 * Returns the name of the \SMW\DataItem deriving class this test tests.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public abstract function getClass();

	/**
	 * First element can be a boolean indication if the successive values are valid,
	 * or a string indicating the type of exception that should be thrown (ie not valid either).
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public abstract function constructorProvider();

	/**
	 * Creates and returns a new instance of the data item.
	 *
	 * @since 1.8
	 *
	 * @return \SMWDataItem
	 */
	public function newInstance() {
		$reflector = new \ReflectionClass( $this->getClass() );
		$args = func_get_args();
		$instance = $reflector->newInstanceArgs( $args );
		return $instance;
	}

	/**
	 * @since 1.8
	 *
	 * @return array
	 */
	public function instanceProvider() {
		$phpFails = array( $this, 'newInstance' );

		return array_filter( array_map(
			function( array $args ) use ( $phpFails ) {
				$isValid = array_shift( $args ) === true;

				if ( $isValid ) {
					return array( call_user_func_array( $phpFails, $args ) );
				}
				else {
					return false;
				}
			},
			$this->constructorProvider()
		), 'is_array' );
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @since 1.8
	 */
	public function testConstructor() {
		$args = func_get_args();

		$valid = array_shift( $args );
		$pokemons = null;

		try {
			$dataItem = call_user_func_array( array( $this, 'newInstance' ), $args );
			$this->assertInstanceOf( '\SMWDataItem', $dataItem );
		}
		catch ( \Exception $pokemons ) {
			if ( $valid === true ) {
				throw $pokemons;
			}

			if ( is_string( $valid ) ) {
				$this->assertEquals( $valid, get_class( $pokemons ) );
			}
			else {
				$this->assertTrue( true );
			}
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @since 1.8
	 *
	 * @param \SMWDataItem $dataItem
	 */
	public function testSerialization( \SMWDataItem $dataItem ) {
		$class = $this->getClass();

		$this->assertEquals(
			$dataItem,
			$class::doUnserialize( $dataItem->getSerialization() )
		);
	}

}