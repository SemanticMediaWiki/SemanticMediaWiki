<?php

namespace SMW\Tests;

use SMW\ParameterProcessorFactory;

/**
 * @covers \SMW\ParameterProcessorFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ParameterProcessorFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'SMW\ParameterProcessorFactory',
			new ParameterProcessorFactory()
		);
	}

	public function testToEliminateFirstParameterIfObject() {
		$parameter = [
			new \stdClass
		];

		$instance = ParameterProcessorFactory::newFromArray( $parameter );

		$this->assertInstanceOf(
			'\SMW\ParserParameterProcessor',
			$instance
		);

		$this->assertEmpty(
			$instance->getRaw()
		);
	}

	public function testNewFromArray() {
		$parameter = [
			'La' => 'Lu'
		];

		$instance = ParameterProcessorFactory::newFromArray( $parameter );

		$this->assertInstanceOf(
			'\SMW\ParserParameterProcessor',
			$instance
		);

		$this->assertEquals(
			$parameter,
			$instance->getRaw()
		);
	}

}
