<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\PropertyChainValue;
use SMW\DIProperty;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\PropertyChainValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyChainValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\PropertyChainValue',
			new PropertyChainValue()
		);
	}

	public function testIsChained() {

		$this->assertFalse(
			PropertyChainValue::isChained( 'Foo' )
		);

		$this->assertTrue(
			PropertyChainValue::isChained( 'Foo.Bar' )
		);
	}

	public function testErrorOnUnchainedValue() {

		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testGetLastPropertyChainValue() {

		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			new DIProperty( 'Bar' ),
			$instance->getLastPropertyChainValue()->getDataItem()
		);

		$this->assertInstanceOf(
			'\SMWDIBlob',
			$instance->getDataItem()
		);
	}

	public function testGetPropertyChainValues() {

		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertCount(
			1,
			$instance->getPropertyChainValues()
		);
	}

	public function testGetWikiValue() {

		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			'Bar',
			$instance->getWikiValue()
		);
	}

	public function testGetShortWikiText() {

		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			'Bar&nbsp;<span title="Foo.Bar">⠉</span>',
			$instance->getShortWikiText()
		);

		$this->assertEquals(
			$this->testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, '[[:Property:Bar|Bar]]&nbsp;<span title="Foo.Bar">⠉</span>' ),
			$instance->getShortWikiText( 'linker' )
		);
	}

	public function testGetLongWikiText() {

		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			$this->testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, 'Property:Bar&nbsp;<span title="Foo.Bar">⠉</span>' ),
			$instance->getLongWikiText()
		);

		$this->assertEquals(
			$this->testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, '[[:Property:Bar|Bar]]&nbsp;<span title="Foo.Bar">⠉</span>' ),
			$instance->getLongWikiText( 'linker' )
		);
	}

	public function testGetShortHTMLText() {

		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			'Bar&nbsp;<span title="Foo.Bar">⠉</span>',
			$instance->getShortHTMLText()
		);
	}

	public function testGetLongHTMLText() {

		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			$this->testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, 'Property:Bar&nbsp;<span title="Foo.Bar">⠉</span>' ),
			$instance->getLongHTMLText()
		);
	}

}
