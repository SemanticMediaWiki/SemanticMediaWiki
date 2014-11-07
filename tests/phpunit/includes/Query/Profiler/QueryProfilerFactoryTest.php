<?php

namespace SMW\Tests\Query\Profiler;

use SMW\Query\Profiler\QueryProfilerFactory;

/**
 * @covers \SMW\Query\Profiler\QueryProfilerFactory
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryProfilerFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Query\Profiler\QueryProfilerFactory',
			new QueryProfilerFactory()
		);
	}

	public function testConstructJointProfileAnnotator() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$instance = new QueryProfilerFactory();

		$this->assertInstanceOf(
			'\SMW\Query\Profiler\ProfileAnnotator',
			$instance->newJointProfileAnnotator( $title, $query, '' )
		);
	}

}
