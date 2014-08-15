<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QueryContainer;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryContainer
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryContainerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QueryContainer',
			new QueryContainer()
		);
	}

	public function testDefaultState() {

		$instance = new QueryContainer( true );

		$this->assertEquals( 0, $instance->queryNumber );
		$this->assertEquals( 't0', $instance->alias );

		$this->assertEquals( 1, $instance::$qnum );
		$this->assertEquals( $instance::Q_TABLE, $instance->type );

		$this->assertEquals( array(), $instance->components );
		$this->assertEquals( array(), $instance->sortfields );

		$this->assertEquals( '', $instance->joinfield );
		$this->assertEquals( '', $instance->jointable );

		$this->assertEquals( '', $instance->from );
		$this->assertEquals( '', $instance->where );
	}

}
