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
	private $testEnvironment;
	private $propertySpecificationLookup;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	public function testCanConstruct() {

		$languageIndependentPropertyLabels = [];
		$canonicalPropertyLabels = [];

		$this->assertInstanceOf(
			'\SMW\PropertyLabelFinder',
			new PropertyLabelFinder( $this->store, $languageIndependentPropertyLabels, $canonicalPropertyLabels )
		);
	}

	public function testPreLoadedPropertyLabel() {

		$languageIndependentPropertyLabels = [ '_Foo' => 'Bar' ];
		$canonicalPropertyLabels = [];

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

		$languageIndependentPropertyLabels = [];
		$canonicalPropertyLabels = [];

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
			[ '_Foo' => 'Bar' ],
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

	public function testPreventKnownPropertyLabelToBeRegisteredAsCanonicalWithDifferentId() {

		$languageIndependentPropertyLabels = [];

		$canonicalPropertyLabels = [
			'Foo' => '_foo'
		];

		$instance = new PropertyLabelFinder(
			$this->store,
			$languageIndependentPropertyLabels,
			$canonicalPropertyLabels
		);

		$instance->registerPropertyLabel(
			'_bar',
			'Foo',
			true
		);

		$this->assertEquals(
			'Foo',
			$instance->findCanonicalPropertyLabelById( '_foo' )
		);
	}

	public function testSearchPropertyIdForNonRegisteredLabel() {

		$languageIndependentPropertyLabels = [];
		$canonicalPropertyLabels = [];

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

	public function testFindPropertyLabelByLanguageCode() {

		$languageIndependentPropertyLabels = [];
		$canonicalPropertyLabels = [];

		$instance = new PropertyLabelFinder(
			$this->store,
			$languageIndependentPropertyLabels,
			$canonicalPropertyLabels
		);

		$this->assertEquals(
			'Booléen',
			$instance->findPropertyLabelFromIdByLanguageCode( '_boo', 'fr' )
		);

		$this->assertEquals(
			'Boolean',
			$instance->findPropertyLabelFromIdByLanguageCode( '_boo', 'en' )
		);
	}

	public function testFindPropertyListFromLabelByLanguageCode() {

		$instance = new PropertyLabelFinder(
			$this->store
		);

		$this->assertEquals(
			[],
			$instance->findPropertyListFromLabelByLanguageCode( '~*unknownProp*', 'ja' )
		);
	}

	public function testFindPreferredPropertyLabelByLanguageCode() {

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getPreferredPropertyLabelByLanguageCode' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'ABC' ) );

		$instance = new PropertyLabelFinder(
			$this->store
		);

		$this->assertEquals(
			'ABC',
			$instance->findPreferredPropertyLabelByLanguageCode( 'Foo', 'fr' )
		);
	}

}
