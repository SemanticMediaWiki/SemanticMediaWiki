<?php

namespace SMW\Tests\Query\Language;

use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ThingDescription;

use SMW\DIWikiPage;
use SMW\Localizer;

/**
 * @covers \SMW\Query\Language\NamespaceDescription
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class NamespaceDescriptionTest extends \PHPUnit_Framework_TestCase {

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

		$this->assertEquals( false, $instance->isSingleton() );
		$this->assertEquals( array(), $instance->getPrintRequests() );

		$this->assertEquals( 1, $instance->getSize() );
		$this->assertEquals( 0, $instance->getDepth() );
		$this->assertEquals( 8, $instance->getQueryFeatures() );
	}

	public function testGetQueryStringForCategoryNamespace() {

		$namespace = NS_CATEGORY;

		$ns = Localizer::getInstance()->getNamespaceTextById( $namespace );
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
		$log      = array();

		$this->assertEquals(
			$instance,
			$instance->prune( $maxsize, $maxDepth, $log )
		);

		$maxsize  = 0;
		$maxDepth = 1;
		$log      = array();

		$this->assertEquals(
			new ThingDescription(),
			$instance->prune( $maxsize, $maxDepth, $log )
		);
	}

}
