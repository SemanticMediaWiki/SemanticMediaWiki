<?php

namespace SMW\Tests;

use SMW\FormatFactory;
use SMW\QueryResultPrinter;
use SMW\TableResultPrinter;
use SMWListResultPrinter;

/**
 * @covers \SMW\FormatFactory
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWQueries
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FormatFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testSingleton() {
		$instance = FormatFactory::singleton();

		$this->assertInstanceOf( FormatFactory::class, $instance );
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

	public function testRegisterFormat() {
		$factory = new FormatFactory();

		$factory->registerFormat( 'table', TableResultPrinter::class );
		$factory->registerFormat( 'list', SMWListResultPrinter::class );

		$this->assertContains( 'table', $factory->getFormats() );
		$this->assertContains( 'list', $factory->getFormats() );
		$this->assertCount( 2, $factory->getFormats() );

		$factory->registerFormat( 'table', SMWListResultPrinter::class );

		$printer = $factory->getPrinter( 'table' );

		$this->assertInstanceOf( SMWListResultPrinter::class, $printer );
	}

	public function testRegisterAliases() {
		$factory = new FormatFactory();

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
			$this->assertInstanceOf( QueryResultPrinter::class, $printer );
		}

		// In case there are no formats PHPUnit would otherwise complain here.
		$this->assertTrue( true );
	}

	public function testGetFormats() {
		$factory = new FormatFactory();

		$this->assertInternalType( 'array', $factory->getFormats() );

		$factory->registerFormat( 'table', TableResultPrinter::class );
		$factory->registerFormat( 'list', SMWListResultPrinter::class );

		$factory->registerAliases( 'foo', array( 'bar' ) );
		$factory->registerAliases( 'foo', array( 'baz' ) );
		$factory->registerAliases( 'ohi', array( 'there', 'o_O' ) );

		$formats = $factory->getFormats();
		$this->assertInternalType( 'array', $formats );

		$this->assertContains( 'table', $factory->getFormats() );
		$this->assertContains( 'list', $factory->getFormats() );
		$this->assertCount( 2, $factory->getFormats() );
	}

	public function testHasFormat() {
		$factory = new FormatFactory();

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
	 */
	public function testGetPrinterException() {
		$this->SetExpectedException( 'MWException' );

		$factory = new FormatFactory();
		$factory->getPrinter( 'lula' );

		$this->assertTrue( true );
	}

	/**
	 * @test FormatFactory::getCanonicalName
	 */
	public function testGetCanonicalNameException() {
		$this->SetExpectedException( 'MWException' );

		$factory = new FormatFactory();
		$factory->getCanonicalName( 9001 );

		$this->assertTrue( true );
	}

	/**
	 * @test FormatFactory::registerFormat
	 * @dataProvider getRegisterFormatExceptioDataProvider
	 */
	public function testRegisterFormatException( $formatName, $class ) {
		$this->SetExpectedException( 'MWException' );

		$factory = new FormatFactory();
		$factory->registerFormat( $formatName, $class );
		$this->assertTrue( true );
	}

	/**
	 * @test FormatFactory::registerAliases
	 * @dataProvider getRegisterAliasesExceptioDataProvider
	 */
	public function testRegisterAliasesException( $formatName, array $aliases ) {
		$this->SetExpectedException( 'MWException' );

		$factory = new FormatFactory();
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
