<?php

namespace SMW\Tests\Unit\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataValues\PropertyChainValue;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\PropertyChainValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyChainValueTest extends TestCase {

	private $testEnvironment;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyChainValue::class,
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
			new Property( 'Bar' ),
			$instance->getLastPropertyChainValue()->getDataItem()
		);

		$this->assertInstanceOf(
			Blob::class,
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
			'Bar<span title="Foo.Bar" class="smw-chain-marker">⠉</span>',
			$instance->getShortWikiText()
		);

		$this->assertEquals(
			$this->testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, '[[:Property:Bar|Bar]]<span title="Foo.Bar" class="smw-chain-marker">⠉</span>' ),
			$instance->getShortWikiText( 'linker' )
		);
	}

	public function testGetLongWikiText() {
		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			$this->testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, 'Property:Bar<span title="Foo.Bar" class="smw-chain-marker">⠉</span>' ),
			$instance->getLongWikiText()
		);

		$this->assertEquals(
			$this->testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, '[[:Property:Bar|Bar]]<span title="Foo.Bar" class="smw-chain-marker">⠉</span>' ),
			$instance->getLongWikiText( 'linker' )
		);
	}

	public function testGetShortHTMLText() {
		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			'Bar<span title="Foo.Bar" class="smw-chain-marker">⠉</span>',
			$instance->getShortHTMLText()
		);
	}

	public function testGetLongHTMLText() {
		$instance = new PropertyChainValue();

		$instance->setUserValue( 'Foo.Bar' );

		$this->assertEquals(
			$this->testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, 'Property:Bar<span title="Foo.Bar" class="smw-chain-marker">⠉</span>' ),
			$instance->getLongHTMLText()
		);
	}

}
