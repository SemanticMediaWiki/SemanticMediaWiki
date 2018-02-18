<?php

namespace SMW\Tests\Rule;

use SMW\Rule\RuleDefinition;

/**
 * @covers \SMW\Rule\RuleDefinition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleDefinitionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new RuleDefinition( 'foo', [] );

		$this->assertInstanceof(
			RuleDefinition::class,
			$instance
		);

		$this->assertInstanceof(
			'\SMW\Rule\RuleDef',
			$instance
		);

		$this->assertInstanceof(
			'\JsonSerializable',
			$instance
		);
	}

	public function testGetName() {

		$instance = new RuleDefinition(
			'foo',
			[]
		);

		$this->assertEquals(
			'foo',
			$instance->getName()
		);
	}

	public function testGetSchema() {

		$instance = new RuleDefinition(
			'foo',
			[],
			'BAR'
		);

		$this->assertEquals(
			'BAR',
			$instance->getSchema()
		);
	}

	public function testGet() {

		$def = [
			'type' => 'foo_bar',
			'description' => 'bar foo bar',
			'rule' => [
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

		$instance = new RuleDefinition(
			'foo',
			$def
		);

		$this->assertEquals(
			'foo_bar',
			$instance->get( RuleDefinition::RULE_TYPE )
		);

		$this->assertEquals(
			[ 'doSomething', 'and' => [ 'doSomethingElse' ] ],
			$instance->get( 'rule.if' )
		);

		$this->assertEquals(
			[ 'doSomethingElse' ],
			$instance->get( 'rule.if.and' )
		);
	}

	public function testJsonSerialize() {

		$def = [
			'type' => 'foo_bar',
			'description' => 'bar foo bar',
			'rule' => [
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

		$instance = new RuleDefinition(
			'foo',
			$def
		);

		$this->assertEquals(
			json_encode( $def ),
			$instance->jsonSerialize()
		);
	}

}
