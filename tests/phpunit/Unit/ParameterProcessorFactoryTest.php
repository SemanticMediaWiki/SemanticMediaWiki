<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\ParameterProcessorFactory;
use SMW\ParserParameterProcessor;
use stdClass;

/**
 * @covers \SMW\ParameterProcessorFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ParameterProcessorFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ParameterProcessorFactory::class,
			new ParameterProcessorFactory()
		);
	}

	public function testToEliminateFirstParameterIfObject() {
		$parameter = [
			new stdClass
		];

		$instance = ParameterProcessorFactory::newFromArray( $parameter );

		$this->assertInstanceOf(
			ParserParameterProcessor::class,
			$instance
		);

		$this->assertEmpty(
			$instance->getRaw()
		);
	}

	public function testObjectFirstParameterIsShiftedButRemainingParametersAreKept() {
		$instance = ParameterProcessorFactory::newFromArray( [ new stdClass, 'Foo=1' ] );

		$this->assertSame(
			[ 'Foo=1' ],
			$instance->getRaw()
		);
	}

	public function testNewFromArray() {
		$parameter = [
			'La' => 'Lu'
		];

		$instance = ParameterProcessorFactory::newFromArray( $parameter );

		$this->assertInstanceOf(
			ParserParameterProcessor::class,
			$instance
		);

		$this->assertEquals(
			$parameter,
			$instance->getRaw()
		);
	}

}
