<?php

namespace SMW\Tests\Rule;

use SMW\Rule\RuleFactory;

/**
 * @covers \SMW\Rule\RuleFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new RuleFactory();

		$this->assertInstanceof(
			RuleFactory::class,
			$instance
		);
	}

	public function testCanConstructJsonSchemaValidator() {

		$instance = new RuleFactory();

		$this->assertInstanceof(
			'\SMW\Utils\JsonSchemaValidator',
			$instance->newJsonSchemaValidator()
		);
	}

	public function testIsRegisteredType() {

		$instance = new RuleFactory(
			[
				'foo' => []
			]
		);

		$this->assertTrue(
			$instance->isRegisteredType( 'foo' )
		);
	}

	public function testGetRegisteredTypes() {

		$instance = new RuleFactory(
			[
				'foo' => [],
				'bar' => []
			]
		);

		$this->assertEquals(
			[ 'foo', 'bar' ],
			$instance->getRegisteredTypes()
		);
	}

	public function testGetRegisteredTypesByGroup() {

		$instance = new RuleFactory(
			[
				'foo' => [ 'group' => 'f_group' ],
				'bar' => [ 'group' => 'b_group' ]
			]
		);

		$this->assertEquals(
			[ 'foo' ],
			$instance->getRegisteredTypesByGroup( 'f_group' )
		);
	}

	public function testNewRuleDefinition() {

		$instance = new RuleFactory(
			[
				'foo' => [ 'group' => 'f_group' ]
			]
		);

		$this->assertInstanceof(
			'\SMW\Rule\RuleDefinition',
			$instance->newRuleDefinition( 'foo_bar', [ 'type' => 'foo' ] )
		);
	}

	public function testNewRuleDefinitionOnUnknownTypeThrowsException() {

		$instance = new RuleFactory();

		$this->setExpectedException( '\SMW\Rule\Exception\RuleTypeNotFoundException' );
		$instance->newRuleDefinition( 'foo_bar', [ 'type' => 'foo' ] );
	}

	public function testNewRuleDefinitionOnNoTypeThrowsException() {

		$instance = new RuleFactory(
			[
				'foo' => [ 'group' => 'f_group' ]
			]
		);

		$this->setExpectedException( '\SMW\Rule\Exception\RuleTypeNotFoundException' );
		$instance->newRuleDefinition( 'foo_bar', [] );
	}

}
