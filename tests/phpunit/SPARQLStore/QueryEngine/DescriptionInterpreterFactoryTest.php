<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\DispatchingDescriptionInterpreter;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DescriptionInterpreterFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DescriptionInterpreterFactory::class,
			new DescriptionInterpreterFactory()
		);
	}

	public function testCanConstructDispatchingDescriptionInterpreter() {
		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionInterpreterFactory();

		$this->assertInstanceOf(
			DispatchingDescriptionInterpreter::class,
			$instance->newDispatchingDescriptionInterpreter( $conditionBuilder )
		);
	}

}
