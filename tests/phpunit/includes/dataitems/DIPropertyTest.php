<?php

namespace SMW\Tests;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\PropertyRegistry;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\DIProperty
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DIPropertyTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	protected function tearDown() {
		PropertyRegistry::clear();
		parent::tearDown();
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testCanConstruct( $arg ) {

		$this->assertInstanceOf(
			DataItem::class,
			new DIProperty( $arg )
		);

		$this->assertInstanceOf(
			DIProperty::class,
			new DIProperty( $arg )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testSerialization( $arg ) {
		$instance = new DIProperty( $arg );

		$this->assertEquals(
			$instance,
			$instance->doUnserialize( $instance->getSerialization() )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testInstanceEqualsItself( $arg ) {

		$instance = new DIProperty( $arg );

		$this->assertTrue(
			$instance->equals( $instance )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testInstanceDoesNotEqualNyanData( $arg ) {

		$instance = new DIProperty( $arg );

		$this->assertFalse(
			$instance->equals( new \SMWDIBlob( '~=[,,_,,]:3' ) )
		);
	}

	public function constructorProvider() {
		return [
			[ 0 ],
			[ 243.35353 ],
			[ 'ohi there' ],
		];
	}

	public function testSetPropertyTypeIdOnUserDefinedProperty() {

		$property = new DIProperty( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$this->assertEquals( '_txt', $property->findPropertyTypeID() );
	}

	public function testSetPropertyTypeIdOnPredefinedProperty() {

		$property = new DIProperty( '_MDAT' );
		$property->setPropertyTypeId( '_dat' );

		$this->assertEquals( '_dat', $property->findPropertyTypeID() );
	}

	public function testSetUnknownPropertyTypeIdThrowsException() {

		$property = new DIProperty( 'SomeUnknownTypeIdProperty' );

		$this->setExpectedException( '\SMW\Exception\DataTypeLookupException' );
		$property->setPropertyTypeId( '_unknownTypeId' );
	}

	public function testSetPropertyTypeIdOnPredefinedPropertyThrowsException() {

		$property = new DIProperty( '_MDAT' );

		$this->setExpectedException( 'RuntimeException' );
		$property->setPropertyTypeId( '_txt' );
	}

	public function testCorrectInversePrefixForPredefinedProperty() {

		$property = new DIProperty( '_SOBJ', true );

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

		$property = new DIProperty( 'Foo' );
		$property->setInterwiki( 'bar' );

		$this->assertEquals(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	public function testCreatePropertyFromLabelThatContainsInverseMarker() {

		$property = DIProperty::newFromUserLabel( '-Foo' );
		$property->setInterwiki( 'bar' );

		$this->assertTrue(
			$property->isInverse()
		);

		$this->assertEquals(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	public function testCreatePropertyFromLabelThatContainsLanguageMarker() {

		$property = DIProperty::newFromUserLabel( '-Foo@en' );
		$property->setInterwiki( 'bar' );

		$this->assertTrue(
			$property->isInverse()
		);

		$this->assertEquals(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY, 'bar' ),
			$property->getDiWikiPage()
		);
	}

	/**
	 * @dataProvider labelProvider
	 */
	public function testNewFromLabel( $label, $iw, $lc, $expected ) {

		$property = DIProperty::newFromUserLabel( $label, $iw, $lc );

		$this->assertEquals(
			$expected,
			$property->getKey()
		);
	}

	public function testCanonicalRepresentation() {

		$property = new DIProperty( '_MDAT' );

		$this->assertEquals(
			'Modification date',
			$property->getCanonicalLabel()
		);

		$this->assertEquals(
			new DIWikiPage( 'Modification_date', SMW_NS_PROPERTY ),
			$property->getCanonicalDiWikiPage()
		);
	}

	public function labelProvider() {

		$provider['testCreatePropertyFromLabelWithAnnotatedLangCodeToTakePrecedence'] = [
			'A le type@fr', '', 'es',
			'_TYPE'
		];

		$provider['testCreatePropertyFromLabelWithExplicitLanguageCode'] = [
			'Fecha de modificación', '', 'es' ,
			'_MDAT'
		];

		$provider['MIMEType'] = [
			'MIME_type', '', 'en',
			'_MIME'
		];

		return $provider;
	}

}
