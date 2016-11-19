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
			'\SMW\SQLStore\TableBuilder\Table',
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
			$instance->getConfiguration()
		);
	}

	public function testAddIndex() {

		$instance = new Table( 'Foo' );

		$instance->addIndex( 'bar' );

		$expected = array(
			'indicies' => array(
				'bar'
			)
		);

		$this->assertEquals(
			$expected,
			$instance->getConfiguration()
		);

		$this->assertInternalType(
			'string',
			$instance->getHash()
		);
	}

	public function testAddIndexWithKey() {

		$instance = new Table( 'Foo' );

		$instance->addIndexWithKey( 'bar', array( 'foobar' ) );

		$expected = array(
			'indicies' => array(
				'bar' => array( 'foobar' )
			)
		);

		$this->assertEquals(
			$expected,
			$instance->getConfiguration()
		);
	}

	public function testAddOption() {

		$instance = new Table( 'Foo' );

		$instance->addOption( 'bar', array( 'foobar' ) );

		$expected = array(
			'bar' => array( 'foobar' )
		);

		$this->assertEquals(
			$expected,
			$instance->getConfiguration()
		);
	}

	public function testAddOptionWithInvalidKeyThrowsException() {

		$instance = new Table( 'Foo' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->addOption( 'fields', array( 'foobar' ) );
	}

}
