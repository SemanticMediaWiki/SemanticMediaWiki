<?php

namespace SMW\Tests\Query\Language;

use SMW\Localizer;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ThingDescription;

/**
 * @covers \SMW\Query\Language\NamespaceDescription
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class NamespaceDescriptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$namespace = NS_MAIN;

		$this->assertInstanceOf(
			'SMW\Query\Language\NamespaceDescription',
			new NamespaceDescription( $namespace )
		);

		// Legacy
		$this->assertInstanceOf(
			'SMW\Query\Language\NamespaceDescription',
			new \SMWNamespaceDescription( $namespace )
		);
	}

	public function testCommonMethods() {
		$namespace = NS_MAIN;

		$instance = new NamespaceDescription( $namespace );

		$this->assertEquals( $namespace, $instance->getNamespace() );

		$this->assertEquals( "[[:+]]", $instance->getQueryString() );
		$this->assertEquals( " <q>[[:+]]</q> ", $instance->getQueryString( true ) );

		$this->assertFalse( $instance->isSingleton() );
		$this->assertEquals( [], $instance->getPrintRequests() );

		$this->assertSame( 1, $instance->getSize() );
		$this->assertSame( 0, $instance->getDepth() );
		$this->assertEquals( 8, $instance->getQueryFeatures() );
	}

	public function testGetQueryStringForCategoryNamespace() {
		$namespace = NS_CATEGORY;

		$ns = Localizer::getInstance()->getNsText( $namespace );
		$instance = new NamespaceDescription( $namespace );

		$this->assertEquals(
			"[[:{$ns}:+]]",
			$instance->getQueryString()
		);

		$this->assertEquals(
			" <q>[[:{$ns}:+]]</q> ",
			$instance->getQueryString( true )
		);
	}

	public function testPrune() {
		$instance = new NamespaceDescription( NS_MAIN );

		$maxsize  = 1;
		$maxDepth = 1;
		$log      = [];

		$this->assertEquals(
			$instance,
			$instance->prune( $maxsize, $maxDepth, $log )
		);

		$maxsize  = 0;
		$maxDepth = 1;
		$log      = [];

		$this->assertEquals(
			new ThingDescription(),
			$instance->prune( $maxsize, $maxDepth, $log )
		);
	}

}
