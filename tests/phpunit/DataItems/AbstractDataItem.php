<?php

namespace SMW\Tests\DataItems;

use ReflectionClass;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;

/**
 * Base class for SMW\DataItems tests.
 *
 * @group SMW
 * @group SMWExtension
 * @group DataItem
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class AbstractDataItem extends SMWIntegrationTestCase {

	/**
	 * Returns the name of the DataItem deriving class this test tests.
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
	 * @return DataItem
	 */
	public function newInstance() {
		$reflector = new ReflectionClass( $this->getClass() );
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

		$this->assertInstanceOf( DataItem::class, $dataItem );
		$this->assertInstanceOf( $this->getClass(), $dataItem );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @since 1.8
	 *
	 * @param DataItem $dataItem
	 */
	public function testSerialization( DataItem $dataItem ) {
		$class = $this->getClass();

		$this->assertEquals(
			$dataItem,
			$class::doUnserialize( $dataItem->getSerialization() )
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testInstanceEqualsItself( DataItem $di ) {
		$this->assertTrue( $di->equals( $di ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testInstanceDoesNotEqualNyanData( DataItem $di ) {
		$this->assertFalse( $di->equals( new Blob( '~=[,,_,,]:3' ) ) );
	}

}
