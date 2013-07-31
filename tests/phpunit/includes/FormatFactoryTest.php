<?php

namespace SMW\Test;
use SMW\FormatFactory;

/**
 * Tests for the SMW\FormatFactory class.
 *
 * @since 1.9
 *
 * @file
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * @covers \SMW\FormatFactory
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWQueries
 */
class FormatFactoryTest extends CompatibilityTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\FormatFactory';
	}

	public function testSingleton() {
		$instance = FormatFactory::singleton();

		$this->assertInstanceOf( 'SMW\FormatFactory', $instance );
		$this->assertTrue( FormatFactory::singleton() === $instance );

		global $smwgResultFormats, $smwgResultAliases;

		foreach ( $smwgResultFormats as $formatName => $printerClass ) {
			$this->assertTrue( $instance->hasFormat( $formatName ) );
			$this->assertInstanceOf( $printerClass, $instance->getPrinter( $formatName ) );
		}

		foreach ( $smwgResultAliases as $formatName => $aliases ) {
			$printerClass = $smwgResultFormats[$formatName];

			foreach ( $aliases as $alias ) {
				$this->assertTrue( $instance->hasFormat( $alias ) );
				$this->assertInstanceOf( $printerClass, $instance->getPrinter( $formatName ) );
			}
		}
	}

	/**
	 * @return FormatFactory
	 */
	protected function getNewInstance() {
//		$reflector = new \ReflectionClass( 'SMW\FormatFactory' );
//		$constructor = $reflector->getConstructor();
//		$constructor->setAccessible( true );
//		return $constructor->invoke( $reflector );
		return new FormatFactory();
	}

	public function testRegisterFormat() {
		$factory = $this->getNewInstance();

		$factory->registerFormat( 'table', 'SMWTablePrinter' );
		$factory->registerFormat( 'list', 'SMWListResultPrinter' );

		$this->assertArrayEquals( array( 'table', 'list' ), $factory->getFormats() );

		$factory->registerFormat( 'table', 'SMWListResultPrinter' );

		$printer = $factory->getPrinter( 'table' );

		$this->assertInstanceOf( 'SMWListResultPrinter', $printer );
	}

	public function testRegisterAliases() {
		$factory = $this->getNewInstance();

		$this->assertEquals( 'foo', $factory->getCanonicalName( 'foo' ) );

		$factory->registerAliases( 'foo', array() );
		$factory->registerAliases( 'foo', array( 'bar' ) );
		$factory->registerAliases( 'foo', array( 'baz' ) );
		$factory->registerAliases( 'ohi', array( 'there', 'o_O' ) );

		$this->assertEquals( 'foo', $factory->getCanonicalName( 'foo' ) );

		$this->assertEquals( 'foo', $factory->getCanonicalName( 'bar' ) );
		$this->assertEquals( 'foo', $factory->getCanonicalName( 'baz' ) );

		$this->assertEquals( 'ohi', $factory->getCanonicalName( 'there' ) );
		$this->assertEquals( 'ohi', $factory->getCanonicalName( 'o_O' ) );

		$factory->registerAliases( 'foo', array( 'o_O' ) );

		$this->assertEquals( 'foo', $factory->getCanonicalName( 'o_O' ) );
	}

	public function testGetPrinter() {
		$factory = FormatFactory::singleton();

		foreach ( $factory->getFormats() as $format ) {
			$printer = $factory->getPrinter( $format );
			$this->assertInstanceOf( 'SMWIResultPrinter', $printer );
		}

		// In case there are no formats PHPUnit would otherwise complain here.
		$this->assertTrue( true );
	}

	public function testGetFormats() {
		$factory = $this->getNewInstance();

		$this->assertInternalType( 'array', $factory->getFormats() );

		$factory->registerFormat( 'table', 'SMWTablePrinter' );
		$factory->registerFormat( 'list', 'SMWListResultPrinter' );

		$factory->registerAliases( 'foo', array( 'bar' ) );
		$factory->registerAliases( 'foo', array( 'baz' ) );
		$factory->registerAliases( 'ohi', array( 'there', 'o_O' ) );

		$formats = $factory->getFormats();
		$this->assertInternalType( 'array', $formats );

		$this->assertArrayEquals( array( 'table', 'list' ), $formats );
	}

	public function testHasFormat() {
		$factory = $this->getNewInstance();

		$this->assertFalse( $factory->hasFormat( 'ohi' ) );

		$factory->registerFormat( 'ohi', 'SMWTablePrinter' );
		$factory->registerAliases( 'ohi', array( 'there', 'o_O' ) );

		$this->assertTrue( $factory->hasFormat( 'ohi' ) );
		$this->assertTrue( $factory->hasFormat( 'there' ) );
		$this->assertTrue( $factory->hasFormat( 'o_O' ) );

		$factory = FormatFactory::singleton();

		foreach ( $factory->getFormats() as $format ) {
			$this->assertTrue( $factory->hasFormat( $format ) );
		}
	}

	/**
	 * @test FormatFactory::getPrinter
	 *
	 * @since 1.9
	 */
	public function testGetPrinterException() {
		$this->SetExpectedException( 'MWException' );

		$factory = $this->getNewInstance();
		$factory->getPrinter( 'lula' );

		$this->assertTrue( true );
	}

	/**
	 * @test FormatFactory::getCanonicalName
	 *
	 * @since 1.9
	 */
	public function testGetCanonicalNameException() {
		$this->SetExpectedException( 'MWException' );

		$factory = $this->getNewInstance();
		$factory->getCanonicalName( 9001 );

		$this->assertTrue( true );
	}

	/**
	 * @test FormatFactory::registerFormat
	 * @dataProvider getRegisterFormatExceptioDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterFormatException( $formatName, $class ) {
		$this->SetExpectedException( 'MWException' );

		$factory = $this->getNewInstance();
		$factory->registerFormat( $formatName, $class );
		$this->assertTrue( true );
	}

	/**
	 * @test FormatFactory::registerAliases
	 * @dataProvider getRegisterAliasesExceptioDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterAliasesException( $formatName, array $aliases ) {
		$this->SetExpectedException( 'MWException' );

		$factory = $this->getNewInstance();
		$factory->registerAliases( $formatName, $aliases );
		$this->assertTrue( true );
	}

	/**
	 * Register format exception data provider
	 *
	 * @return array
	 */
	public function getRegisterFormatExceptioDataProvider() {
		return array(
			array( 1001, 'Foo' ),
			array( 'Foo', 9001 ),
		);
	}

	/**
	 * Register aliases exception data provider
	 *
	 * @return array
	 */
	public function getRegisterAliasesExceptioDataProvider() {
		return array(
			array( 1001, array( 'Foo' => 'Bar' ) ),
			array( 'Foo', array( 'Foo' => 9001 ) ),
		);
	}
}