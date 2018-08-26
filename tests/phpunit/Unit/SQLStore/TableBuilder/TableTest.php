<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\Table;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\Table
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Table::class,
			new Table( 'Foo' )
		);
	}

	public function testAddColumn() {

		$instance = new Table( 'Foo' );

		$instance->addColumn( 'b', 'integer' );

		$expected = array(
			'fields' => array(
				 'b' => 'integer'
			)
		);

		$this->assertEquals(
			$expected,
			$instance->getAttributes()
		);
	}

	public function testAddIndex() {

		$instance = new Table( 'Foo' );

		$instance->addIndex( 'bar' );

		$expected = array(
			'indices' => array(
				'bar'
			)
		);

		$this->assertEquals(
			$expected,
			$instance->getAttributes()
		);

		$this->assertInternalType(
			'string',
			$instance->getHash()
		);
	}

	public function testAddIndexWithKey() {

		$instance = new Table( 'Foo' );

		$instance->addIndex( array( 'foobar' ), 'bar' );

		$expected = array(
			'indices' => array(
				'bar' => array( 'foobar' )
			)
		);

		$this->assertEquals(
			$expected,
			$instance->getAttributes()
		);
	}

	public function testAddOption() {

		$instance = new Table( 'Foo' );

		$instance->addOption( 'bar', [ 'foobar' ] );

		$expected = [
			'bar' => [ 'foobar' ]
		];

		$this->assertEquals(
			$expected,
			$instance->getAttributes()
		);

		$this->assertEquals(
			[ 'foobar' ],
			$instance->get( 'bar' )
		);
	}

	public function testGetOnUnregsiteredKeyThrowsException() {

		$instance = new Table( 'Foo' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->get( 'bar' );
	}

	/**
	 * @dataProvider invalidOptionsProvider
	 */
	public function testAddOptionOnReservedOptionKeyThrowsException( $key ) {

		$instance = new Table( 'Foo' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->addOption( $key, [] );
	}

	public function invalidOptionsProvider() {

		$provider[] = [
			'fields'
		];

		$provider[] = [
			'indices'
		];

		$provider[] = [
			'defaults'
		];

		return $provider;
	}

}
