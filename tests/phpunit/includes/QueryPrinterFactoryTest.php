<?php

namespace SMW\Tests;

use SMW\QueryPrinterFactory;
use SMW\QueryResultPrinter;
use SMW\TableResultPrinter;
use SMWListResultPrinter;

/**
 * @covers \SMW\QueryPrinterFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class QueryPrinterFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testSingleton() {
		$instance = QueryPrinterFactory::singleton();

		$this->assertInstanceOf( QueryPrinterFactory::class, $instance );
		$this->assertTrue( QueryPrinterFactory::singleton() === $instance );

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
		$factory = new QueryPrinterFactory();

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
		$factory = new QueryPrinterFactory();

		$this->assertEquals( 'foo', $factory->getCanonicalName( 'foo' ) );

		$factory->registerAliases( 'foo', [] );
		$factory->registerAliases( 'foo', [ 'bar' ] );
		$factory->registerAliases( 'foo', [ 'baz' ] );
		$factory->registerAliases( 'ohi', [ 'there', 'o_O' ] );

		$this->assertEquals( 'foo', $factory->getCanonicalName( 'foo' ) );

		$this->assertEquals( 'foo', $factory->getCanonicalName( 'bar' ) );
		$this->assertEquals( 'foo', $factory->getCanonicalName( 'baz' ) );

		$this->assertEquals( 'ohi', $factory->getCanonicalName( 'there' ) );
		$this->assertEquals( 'ohi', $factory->getCanonicalName( 'o_O' ) );

		$factory->registerAliases( 'foo', [ 'o_O' ] );

		$this->assertEquals( 'foo', $factory->getCanonicalName( 'o_O' ) );
	}

	public function testGetPrinter() {
		$factory = QueryPrinterFactory::singleton();

		foreach ( $factory->getFormats() as $format ) {
			$printer = $factory->getPrinter( $format );
			$this->assertInstanceOf( QueryResultPrinter::class, $printer );
		}

		// In case there are no formats PHPUnit would otherwise complain here.
		$this->assertTrue( true );
	}

	public function testGetFormats() {
		$factory = new QueryPrinterFactory();

		$this->assertInternalType( 'array', $factory->getFormats() );

		$factory->registerFormat( 'table', TableResultPrinter::class );
		$factory->registerFormat( 'list', SMWListResultPrinter::class );

		$factory->registerAliases( 'foo', [ 'bar' ] );
		$factory->registerAliases( 'foo', [ 'baz' ] );
		$factory->registerAliases( 'ohi', [ 'there', 'o_O' ] );

		$formats = $factory->getFormats();
		$this->assertInternalType( 'array', $formats );

		$this->assertContains( 'table', $factory->getFormats() );
		$this->assertContains( 'list', $factory->getFormats() );
		$this->assertCount( 2, $factory->getFormats() );
	}

	public function testHasFormat() {
		$factory = new QueryPrinterFactory();

		$this->assertFalse( $factory->hasFormat( 'ohi' ) );

		$factory->registerFormat( 'ohi', 'SMWTablePrinter' );
		$factory->registerAliases( 'ohi', [ 'there', 'o_O' ] );

		$this->assertTrue( $factory->hasFormat( 'ohi' ) );
		$this->assertTrue( $factory->hasFormat( 'there' ) );
		$this->assertTrue( $factory->hasFormat( 'o_O' ) );

		$factory = QueryPrinterFactory::singleton();

		foreach ( $factory->getFormats() as $format ) {
			$this->assertTrue( $factory->hasFormat( $format ) );
		}
	}

	public function testGetPrinterThrowsException() {

		$factory = new QueryPrinterFactory();

		$this->setExpectedException( '\SMW\Query\Exception\ResultFormatNotFoundException' );
		$factory->getPrinter( 'lula' );
	}

	public function testGetCanonicalNameThrowsException() {

		$factory = new QueryPrinterFactory();

		$this->setExpectedException( 'InvalidArgumentException' );
		$factory->getCanonicalName( 9001 );
	}

	/**
	 * @dataProvider registerFormatExceptioProvider
	 */
	public function testRegisterFormatThrowsException( $formatName, $class ) {

		$factory = new QueryPrinterFactory();

		$this->setExpectedException( 'InvalidArgumentException' );
		$factory->registerFormat( $formatName, $class );
	}

	/**
	 * Register format exception data provider
	 *
	 * @return array
	 */
	public function registerFormatExceptioProvider() {
		return [
			[ 1001, 'Foo' ],
			[ 'Foo', 9001 ],
		];
	}

	/**
	 * @dataProvider registerAliasesExceptionProvider
	 */
	public function testRegisterAliasesThrowsException( $formatName, array $aliases ) {

		$factory = new QueryPrinterFactory();

		$this->setExpectedException( 'InvalidArgumentException' );
		$factory->registerAliases( $formatName, $aliases );
	}

	/**
	 * Register aliases exception data provider
	 *
	 * @return array
	 */
	public function registerAliasesExceptionProvider() {
		return [
			[ 1001, [ 'Foo' => 'Bar' ] ],
			[ 'Foo', [ 'Foo' => 9001 ] ],
		];
	}

}
