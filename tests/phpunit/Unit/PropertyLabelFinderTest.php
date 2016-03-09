<?php

namespace SMW\Tests;

use SMW\PropertyLabelFinder;

/**
 * @covers \SMW\PropertyLabelFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PropertyLabelFinderTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$languageIndependentPropertyLabels = array();
		$canonicalPropertyLabels = array();

		$this->assertInstanceOf(
			'\SMW\PropertyLabelFinder',
			new PropertyLabelFinder( $this->store, $languageIndependentPropertyLabels, $canonicalPropertyLabels )
		);
	}

	public function testPreLoadedPropertyLabel() {

		$languageIndependentPropertyLabels = array( '_Foo' => 'Bar' );
		$canonicalPropertyLabels = array();

		$instance = new PropertyLabelFinder(
			$this->store,
			$languageIndependentPropertyLabels,
			$canonicalPropertyLabels
		);

		$this->assertEquals(
			'Bar',
			$instance->findPropertyLabelById( '_Foo' )
		);

		$this->assertEquals(
			'_Foo',
			$instance->searchPropertyIdByLabel( 'Bar' )
		);
	}

	public function testRegisterPropertyLabel() {

		$languageIndependentPropertyLabels = array();
		$canonicalPropertyLabels = array();

		$instance = new PropertyLabelFinder(
			$this->store,
			$languageIndependentPropertyLabels,
			$canonicalPropertyLabels
		);

		$instance->registerPropertyLabel(
			'_Foo',
			'Bar'
		);

		$this->assertEquals(
			array( '_Foo' => 'Bar' ),
			$instance->getKownPredefinedPropertyLabels()
		);

		$this->assertEquals(
			'Bar',
			$instance->findPropertyLabelById( '_Foo' )
		);

		$this->assertEquals(
			'_Foo',
			$instance->searchPropertyIdByLabel( 'Bar' )
		);

		$this->assertEquals(
			'Bar',
			$instance->findCanonicalPropertyLabelById( '_Foo' )
		);
	}

	public function testSearchPropertyIdForNonRegisteredLabel() {

		$languageIndependentPropertyLabels = array();
		$canonicalPropertyLabels = array();

		$instance = new PropertyLabelFinder(
			$this->store,
			$languageIndependentPropertyLabels,
			$canonicalPropertyLabels
		);

		$this->assertFalse(
			$instance->searchPropertyIdByLabel( 'Bar' )
		);

		$this->assertEquals(
			'',
			$instance->findPropertyLabelById( '_Foo' )
		);
	}

}
