<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionInterpreterFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory',
			new DescriptionInterpreterFactory()
		);
	}

	public function testCanConstructDispatchingDescriptionInterpreter() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionInterpreterFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\DispatchingDescriptionInterpreter',
			$instance->newDispatchingDescriptionInterpreter( $querySegmentListBuilder )
		);
	}

}
