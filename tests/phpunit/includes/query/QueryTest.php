<?php

namespace SMW\Tests;

use SMWQuery as Query;

/**
 * @covers \SMWQuery
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$this->assertInstanceOf(
			'\SMWQuery',
			new Query( $description )
		);
	}

	public function testSetGetLimitForInlineQueryWhereUpperboundIsRestrictedByGLOBALRequirements() {

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new Query( $description, true, false );

		$upperboundLimit = 999999999;
		$lowerboundLimit = 1;

		$smwgQMaxLimit = $GLOBALS['smwgQMaxLimit'];
		$smwgQMaxInlineLimit = $GLOBALS['smwgQMaxInlineLimit'];

		$this->assertLessThan( $upperboundLimit, $smwgQMaxLimit );
		$this->assertLessThan( $upperboundLimit, $smwgQMaxInlineLimit );

		$this->assertGreaterThan( $lowerboundLimit, $smwgQMaxLimit );
		$this->assertGreaterThan( $lowerboundLimit, $smwgQMaxInlineLimit );

		$instance->setLimit( $upperboundLimit, true );
		$this->assertEquals( $smwgQMaxInlineLimit, $instance->getLimit() );

		$instance->setLimit( $upperboundLimit, false );
		$this->assertEquals( $smwgQMaxLimit, $instance->getLimit() );

		$instance->setLimit( $lowerboundLimit, true );
		$this->assertEquals( $lowerboundLimit, $instance->getLimit() );

		$instance->setLimit( $lowerboundLimit, false );
		$this->assertEquals( $lowerboundLimit, $instance->getLimit() );
	}

	public function testSetGetLimitForInlineQueryWhereUnboundLimitIsUnrestricted() {

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new Query( $description, true, false );

		$upperboundLimit = 999999999;
		$lowerboundLimit = 1;

		$instance->setUnboundLimit( $upperboundLimit );
		$this->assertEquals( $upperboundLimit, $instance->getLimit() );

		$instance->setUnboundLimit( $lowerboundLimit );
		$this->assertEquals( $lowerboundLimit, $instance->getLimit() );
	}

}
