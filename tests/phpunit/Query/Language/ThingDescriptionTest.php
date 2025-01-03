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
class ThingDescriptionTest extends \PHPUnit\Framework\TestCase {

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

		$this->assertSame( '', $instance->getQueryString() );
		$this->assertFalse( $instance->isSingleton() );
		$this->assertEquals( [], $instance->getPrintRequests() );

		$this->assertSame( 0, $instance->getSize() );
		$this->assertSame( 0, $instance->getDepth() );
		$this->assertSame( 0, $instance->getQueryFeatures() );
	}

	public function testPrune() {
		$instance = new ThingDescription();

		$maxsize  = 1;
		$maxDepth = 1;
		$log      = [];

		$this->assertEquals(
			$instance,
			$instance->prune( $maxsize, $maxDepth, $log )
		);
	}

}
