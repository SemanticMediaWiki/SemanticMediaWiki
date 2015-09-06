<?php

namespace SMW\Tests\Query\Language;

use SMW\Query\Language\ThingDescription;

/**
 * @covers \SMW\Query\Language\ThingDescription
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ThingDescriptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\Query\Language\ThingDescription',
			new ThingDescription()
		);

		// Legacy
		$this->assertInstanceOf(
			'SMW\Query\Language\ThingDescription',
			new \SMWThingDescription()
		);
	}

	public function testCommonMethods() {

		$instance = new ThingDescription();

		$this->assertEquals( '', $instance->getQueryString() );
		$this->assertEquals( false, $instance->isSingleton() );
		$this->assertEquals( array(), $instance->getPrintRequests() );

		$this->assertEquals( 0, $instance->getSize() );
		$this->assertEquals( 0, $instance->getDepth() );
		$this->assertEquals( 0, $instance->getQueryFeatures() );
	}

	public function testPrune() {

		$instance = new ThingDescription();

		$maxsize  = 1;
		$maxDepth = 1;
		$log      = array();

		$this->assertEquals(
			$instance,
			$instance->prune( $maxsize, $maxDepth, $log )
		);
	}

}
