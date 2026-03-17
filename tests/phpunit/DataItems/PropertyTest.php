<?php

namespace SMW\Tests;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Exception\DataTypeLookupException;
use SMW\PropertyRegistry;

/**
 * @covers \SMW\DataItems\Property
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyTest extends TestCase {

	protected function tearDown(): void {
		PropertyRegistry::clear();
		parent::tearDown();
	}

	public function testSerialization() {
		$instance = new Property( 'ohi there' );

		$this->assertEquals(
			$instance,
			$instance->doUnserialize( $instance->getSerialization() )
		);
	}

	public function testInstanceEqualsItself() {
		$instance = new Property( 'ohi there' );

		$this->assertTrue(
			$instance->equals( $instance )
		);
	}

	public function testInstanceDoesNotEqualNyanData() {
		$instance = new Property( 'ohi there' );

		$this->assertFalse(
			$instance->equals( new Blob( '~=[,,_,,]:3' ) )
		);
	}

	public function testSetPropertyTypeIdOnUserDefinedProperty() {
		$property = new Property( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$this->assertEquals( '_txt', $property->findPropertyTypeID() );
	}

	public function testSetPropertyTypeIdOnPredefinedProperty() {
		$property = new Property( '_MDAT' );
		$property->setPropertyTypeId( '_dat' );

		$this->assertEquals( '_dat', $property->findPropertyTypeID() );
	}

	public function testSetUnknownPropertyTypeIdThrowsException() {
		$property = new Property( 'SomeUnknownTypeIdProperty' );

		$this->expectException( DataTypeLookupException::class );
		$property->setPropertyTypeId( '_unknownTypeId' );
	}

	public function testSetPropertyTypeIdOnPredefinedPropertyThrowsException() {
		$property = new Property( '_MDAT' );

		$this->expectException( 'RuntimeException' );
		$property->setPropertyTypeId( '_txt' );
	}

	public function testCorrectInversePrefixForPredefinedProperty() {
		$property = new Property( '_SOBJ', true );

		$this->assertTrue(
			$property->isInverse()
		);

		$label = $property->getLabel();

		$this->assertEquals(
			'-',
			$label[0]
		);
	}

	public function testUseInterwikiPrefix() {
		$property = new Property( 'Foo' );
		$property->setInterwiki( 'bar' );

		$this->assertEquals(
			new WikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	public function testCreatePropertyFromLabelThatContainsInverseMarker() {
		$property = Property::newFromUserLabel( '-Foo' );
		$property->setInterwiki( 'bar' );

		$this->assertTrue(
			$property->isInverse()
		);

		$this->assertEquals(
			new WikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	public function testCreatePropertyFromLabelThatContainsLanguageMarker() {
		$property = Property::newFromUserLabel( '-Foo@en' );
		$property->setInterwiki( 'bar' );

		$this->assertTrue(
			$property->isInverse()
		);

		$this->assertEquals(
			new WikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	/**
	 * @dataProvider labelProvider
	 */
	public function testNewFromLabel( $label, $iw, $lc, $expected ) {
		$property = Property::newFromUserLabel( $label, $iw, $lc );

		$this->assertEquals(
			$expected,
			$property->getKey()
		);
	}

	public function testCanonicalRepresentation() {
		$property = new Property( '_MDAT' );

		$this->assertEquals(
			'Modification date',
			$property->getCanonicalLabel()
		);

		$this->assertEquals(
			new WikiPage( 'Modification_date', SMW_NS_PROPERTY ),
			$property->getCanonicalDiWikiPage()
		);
	}

	public function labelProvider() {
		$provider['testCreatePropertyFromLabelWithAnnotatedLangCodeToTakePrecedence'] = [
			'A le type@fr', '', 'es',
			'_TYPE'
		];

		$provider['testCreatePropertyFromLabelWithExplicitLanguageCode'] = [
			'Fecha de modificación', '', 'es',
			'_MDAT'
		];

		$provider['MIMEType'] = [
			'MIME_type', '', 'en',
			'_MIME'
		];

		return $provider;
	}

}
