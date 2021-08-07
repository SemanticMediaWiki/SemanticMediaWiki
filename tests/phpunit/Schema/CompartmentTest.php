<?php

namespace SMW\Tests\Schema;

use SMW\Schema\Compartment;

/**
 * @covers \SMW\Schema\Compartment
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CompartmentTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceof(
			Compartment::class,
			new Compartment()
		);
	}

	public function testIsEmpty() {

		$instance = new Compartment();

		$this->assertTrue(
			$instance->isEmpty()
		);
	}

	public function testHas() {

		$data = [
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

		$instance = new Compartment(
			$data
		);

		$this->assertTrue(
			$instance->has( 'Schema.if' )
		);

		$this->assertFalse(
			$instance->has( 'Schema.if.xyz' )
		);
	}

	public function testGet() {

		$data = [
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

		$instance = new Compartment(
			$data
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

		$data = [
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

		$instance = new Compartment(
			$data
		);

		$this->assertEquals(
			json_encode( $data ),
			$instance->jsonSerialize()
		);
	}

	public function testIteratorAggregate() {

		$data = [
			'section_1' => [
				'if' => [
					'doSomething',
					'and' => [
						'doSomethingElse'
					]
				],
				'then' => [
				]
			],
			'not_used',
			'___assoc_schema' => 'foo_schema'
		];

		$instance = new Compartment(
			$data
		);

		$this->assertEquals(
			'foo_schema',
			$instance->get( Compartment::ASSOCIATED_SCHEMA )
		);

		foreach ( $instance as $compartment ) {
			$this->assertInstanceof(
				Compartment::class,
				$compartment
			);

			$this->assertEquals(
				'foo_schema',
				$compartment->get( Compartment::ASSOCIATED_SCHEMA )
			);

			$this->assertEquals(
				'section_1',
				$compartment->get( Compartment::ASSOCIATED_SECTION )
			);
		}
	}

}
