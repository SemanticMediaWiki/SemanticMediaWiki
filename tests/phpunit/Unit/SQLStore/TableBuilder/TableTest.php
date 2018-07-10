<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\Table;

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

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Table::class,
			new Table( 'Foo' )
		);
	}

	public function testAddColumn() {

		$instance = new Table( 'Foo' );

		$instance->addColumn( 'b', 'integer' );

		$expected = [
			'fields' => [
				 'b' => 'integer'
			]
		];

		$this->assertEquals(
			$expected,
			$instance->getOptions()
		);
	}

	public function testAddIndex() {

		$instance = new Table( 'Foo' );

		$instance->addIndex( 'bar' );

		$expected = [
			'indices' => [
				'bar'
			]
		];

		$this->assertEquals(
			$expected,
			$instance->getOptions()
		);

		$this->assertInternalType(
			'string',
			$instance->getHash()
		);
	}

	public function testAddIndexWithKey() {

		$instance = new Table( 'Foo' );

		$instance->addIndex( [ 'foobar' ], 'bar' );

		$expected = [
			'indices' => [
				'bar' => [ 'foobar' ]
			]
		];

		$this->assertEquals(
			$expected,
			$instance->getOptions()
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
			$instance->getOptions()
		);

		$this->assertEquals(
			[ 'foobar' ],
			$instance->getOption( 'bar' )
		);
	}

	public function testGetOptionOnUnregsiteredKeyThrowsException() {

		$instance = new Table( 'Foo' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getOption( 'bar' );
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
