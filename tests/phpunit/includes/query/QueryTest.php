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

	private $smwgQMaxLimit;
	private $smwgQMaxInlineLimit;

	protected function setUp() {
		parent::setUp();

		$this->smwgQMaxLimit = $GLOBALS['smwgQMaxLimit'];
		$this->smwgQMaxInlineLimit = $GLOBALS['smwgQMaxInlineLimit'];
	}

	public function testCanConstruct() {

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$this->assertInstanceOf(
			'\SMWQuery',
			new Query( $description )
		);
	}

	public function testSetGetLimitForLowerbound() {

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new Query( $description, true, false );

		$lowerboundLimit = 1;

		$this->assertGreaterThan( $lowerboundLimit, $this->smwgQMaxLimit );
		$this->assertGreaterThan( $lowerboundLimit, $this->smwgQMaxInlineLimit );

		$instance->setLimit( $lowerboundLimit, true );
		$this->assertEquals( $lowerboundLimit, $instance->getLimit() );

		$instance->setLimit( $lowerboundLimit, false );
		$this->assertEquals( $lowerboundLimit, $instance->getLimit() );
	}

	public function testSetGetLimitForUpperboundWhereLimitIsRestrictedByGLOBALRequirements() {

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new Query( $description, true, false );

		$upperboundLimit = 999999999;

		$this->assertLessThan( $upperboundLimit, $this->smwgQMaxLimit );
		$this->assertLessThan( $upperboundLimit, $this->smwgQMaxInlineLimit );

		$instance->setLimit( $upperboundLimit, true );
		$this->assertEquals( $this->smwgQMaxInlineLimit, $instance->getLimit() );

		$instance->setLimit( $upperboundLimit, false );
		$this->assertEquals( $this->smwgQMaxLimit, $instance->getLimit() );
	}

	public function testSetGetLimitForUpperboundWhereLimitIsUnrestricted() {

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new Query( $description, true, false );

		$upperboundLimit = 999999999;

		$this->assertLessThan( $upperboundLimit, $this->smwgQMaxLimit );
		$this->assertLessThan( $upperboundLimit, $this->smwgQMaxInlineLimit );

		$instance->setUnboundLimit( $upperboundLimit );
		$this->assertEquals( $upperboundLimit, $instance->getLimit() );
	}

}
