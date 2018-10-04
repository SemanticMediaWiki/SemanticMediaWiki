<?php

namespace SMW\Tests\Query\Processor;

use SMW\Query\Processor\DefaultParamDefinition;

/**
 * @covers \SMW\Query\Processor\DefaultParamDefinition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DefaultParamDefinitionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DefaultParamDefinition::class,
			new DefaultParamDefinition()
		);
	}

	public function testBuildParamDefinitions() {

		$vars = [
			'smwgResultFormats' => [],
			'smwgResultAliases' => [],
			'smwgQuerySources' => [],
			'smwgQDefaultLimit' => 42,
			'smwgQUpperbound' => 100
		];

		$this->assertInternalType(
			'array',
			DefaultParamDefinition::buildParamDefinitions( $vars )
		);
	}

	public function testGetParamDefinitions() {

		$this->assertInternalType(
			'array',
			DefaultParamDefinition::getParamDefinitions()
		);
	}

}