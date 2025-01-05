<?php

namespace SMW\Tests;

use SMWDataItem;

/**
 * Base class for SMW\DataItem tests.
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWDataItems
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class DataItemTest extends SMWIntegrationTestCase {

	/**
	 * Returns the name of the \SMW\DataItem deriving class this test tests.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	abstract public function getClass();

	/**
	 * @since 1.8
	 *
	 * @return array
	 */
	abstract public function constructorProvider();

	/**
	 * Creates and returns a new instance of the data item.
	 *
	 * @since 1.8
	 *
	 * @return SMWDataItem
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
		$phpFails = [ $this, 'newInstance' ];

		return array_map(
			static function ( array $args ) use ( $phpFails ) {
				return [ call_user_func_array( $phpFails, $args ) ];
			},
			$this->constructorProvider()
		);
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @since 1.8
	 */
	public function testConstructor() {
		$dataItem = call_user_func_array(
			[ $this, 'newInstance' ],
			func_get_args()
		);

		$this->assertInstanceOf( '\SMWDataItem', $dataItem );
		$this->assertInstanceOf( $this->getClass(), $dataItem );
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

	/**
	 * @dataProvider instanceProvider
	 */
	public function testInstanceEqualsItself( SMWDataItem $di ) {
		$this->assertTrue( $di->equals( $di ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testInstanceDoesNotEqualNyanData( SMWDataItem $di ) {
		$this->assertFalse( $di->equals( new \SMWDIBlob( '~=[,,_,,]:3' ) ) );
	}

}
