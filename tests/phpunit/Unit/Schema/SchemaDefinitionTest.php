<?php

namespace SMW\Tests\Schema;

use SMW\Schema\SchemaDefinition;

/**
 * @covers \SMW\Schema\SchemaDefinition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaDefinitionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new SchemaDefinition( 'foo', [] );

		$this->assertInstanceof(
			SchemaDefinition::class,
			$instance
		);

		$this->assertInstanceof(
			'\SMW\Schema\Schema',
			$instance
		);

		$this->assertInstanceof(
			'\JsonSerializable',
			$instance
		);
	}

	public function testGetName() {

		$instance = new SchemaDefinition(
			'foo',
			[]
		);

		$this->assertEquals(
			'foo',
			$instance->getName()
		);
	}

	public function testGetSchemaLink() {

		$instance = new SchemaDefinition(
			'foo',
			[],
			'BAR'
		);

		$this->assertEquals(
			'BAR',
			$instance->getValidationSchema()
		);
	}

	public function testGet() {

		$def = [
			'type' => 'foo_bar',
			'description' => 'bar foo bar',
			'Schema' => [
				'if' => [
					'doSomething',
					'and' => [
						'doSomethingElse'
					]
				],
				'then' => [
				]
			]
		];

		$instance = new SchemaDefinition(
			'foo',
			$def
		);

		$this->assertEquals(
			'foo_bar',
			$instance->get( SchemaDefinition::SCHEMA_TYPE )
		);

		$this->assertEquals(
			[ 'doSomething', 'and' => [ 'doSomethingElse' ] ],
			$instance->get( 'Schema.if' )
		);

		$this->assertEquals(
			[ 'doSomethingElse' ],
			$instance->get( 'Schema.if.and' )
		);
	}

	public function testJsonSerialize() {

		$def = [
			'type' => 'foo_bar',
			'description' => 'bar foo bar',
			'Schema' => [
				'if' => [
					'doSomething',
					'and' => [
						'doSomethingElse'
					]
				],
				'then' => [
				]
			]
		];

		$instance = new SchemaDefinition(
			'foo',
			$def
		);

		$this->assertEquals(
			json_encode( $def ),
			$instance->jsonSerialize()
		);
	}

}
