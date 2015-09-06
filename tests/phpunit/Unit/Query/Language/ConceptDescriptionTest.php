<?php

namespace SMW\Tests\Query\Language;

use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\ThingDescription;

use SMW\DIWikiPage;
use SMW\Localizer;

/**
 * @covers \SMW\Query\Language\ConceptDescription
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConceptDescriptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$concept = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\Query\Language\ConceptDescription',
			new ConceptDescription( $concept )
		);

		// Legacy
		$this->assertInstanceOf(
			'SMW\Query\Language\ConceptDescription',
			new \SMWConceptDescription( $concept )
		);
	}

	public function testCommonMethods() {

		$ns = Localizer::getInstance()->getNamespaceTextById( SMW_NS_CONCEPT );

		$concept = new DIWikiPage( 'Foo', SMW_NS_CONCEPT );
		$instance = new ConceptDescription( $concept );

		$this->assertEquals( $concept, $instance->getConcept() );

		$this->assertEquals( "[[{$ns}:Foo]]", $instance->getQueryString() );
		$this->assertEquals( " <q>[[{$ns}:Foo]]</q> ", $instance->getQueryString( true ) );

		$this->assertEquals( false, $instance->isSingleton() );
		$this->assertEquals( array(), $instance->getPrintRequests() );

		$this->assertEquals( 1, $instance->getSize() );
		$this->assertEquals( 0, $instance->getDepth() );
		$this->assertEquals( 4, $instance->getQueryFeatures() );
	}

	public function testPrune() {

		$instance = new ConceptDescription( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) );

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
