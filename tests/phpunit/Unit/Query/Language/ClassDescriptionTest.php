<?php

namespace SMW\Tests\Query\Language;

use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ThingDescription;

use SMW\DIWikiPage;
use SMW\Localizer;

/**
 * @covers \SMW\Query\Language\ClassDescription
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ClassDescriptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$class = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\Query\Language\ClassDescription',
			new ClassDescription( $class )
		);

		// Legacy
		$this->assertInstanceOf(
			'SMW\Query\Language\ClassDescription',
			new \SMWClassDescription( $class )
		);
	}

	public function testConstructThrowsException() {

		$this->setExpectedException( 'Exception' );

		new ClassDescription( new \stdClass );
	}

	public function testCommonMethods() {

		$ns = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );

		$class = new DIWikiPage( 'Foo', NS_CATEGORY );
		$instance = new ClassDescription( $class );

		$this->assertEquals( array( $class ), $instance->getCategories() );

		$this->assertEquals( "[[{$ns}:Foo]]", $instance->getQueryString() );
		$this->assertEquals( " <q>[[{$ns}:Foo]]</q> ", $instance->getQueryString( true ) );

		$this->assertEquals( false, $instance->isSingleton() );
		$this->assertEquals( array(), $instance->getPrintRequests() );

		$this->assertEquals( 1, $instance->getSize() );
		$this->assertEquals( 0, $instance->getDepth() );
		$this->assertEquals( 2, $instance->getQueryFeatures() );
	}

	public function testAddDescription() {

		$ns = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );

		$instance = new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) );
		$instance->addDescription( new ClassDescription( new DIWikiPage( 'Bar', NS_CATEGORY ) ) );

		$this->assertEquals(
			"[[{$ns}:Foo||Bar]]",
			$instance->getQueryString()
		);

		$this->assertEquals(
			" <q>[[{$ns}:Foo||Bar]]</q> ",
			$instance->getQueryString( true )
		);
	}

	public function testPrune() {

		$instance = new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) );

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
