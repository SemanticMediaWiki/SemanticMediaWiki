<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\TypesValue;
use SMW\DataValues\ValueParsers\TypesValueParser;

/**
 * @covers \SMW\DataValues\TypesValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TypesValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TypesValue::class,
			new TypesValue()
		);

		// FIXME Legacy naming remove in 3.1.x
		$this->assertInstanceOf(
			'\SMWTypesValue',
			new TypesValue()
		);
	}

	public function testNewFromTypeId() {

		$this->assertInstanceOf(
			TypesValue::class,
			TypesValue::newFromTypeId( '_dat' )
		);
	}

	/**
	 * @dataProvider typeLabelProvider
	 */
	public function testNewFromTypeId_GetWikiValue( $type, $label ) {

		$this->assertEquals(
			$label,
			TypesValue::newFromTypeId( $type )->getWikiValue()
		);
	}

	/**
	 * @dataProvider typeUriProvider
	 */
	public function testGetTypeUriFromTypeId( $type, $url ) {

		$this->assertEquals(
			$url,
			TypesValue::getTypeUriFromTypeId( $type )->getUri()
		);
	}

	/**
	 * @dataProvider userWikiValueProvider
	 */
	public function testSetUserValue_GetWikiValue( $value, $expected ) {

		$instance = new TypesValue();
		$instance->setUserValue( $value );

		$this->assertEquals(
			$expected,
			$instance->getWikiValue()
		);
	}

	public function typeLabelProvider() {

		yield [
			'_dat',
			'Date'
		];

		yield [
			'_boo',
			'Boolean'
		];

		yield [
			'foo',
			''
		];
	}

	public function typeUriProvider() {

		yield [
			'_dat',
			'http://semantic-mediawiki.org/swivt/1.0#_dat'
		];

		yield [
			'_boo',
			'http://semantic-mediawiki.org/swivt/1.0#_boo'
		];

		yield [
			'foo',
			'http://semantic-mediawiki.org/swivt/1.0#foo'
		];
	}

	public function userWikiValueProvider() {

		yield [
			'_dat',
			'Date'
		];

		yield [
			'Date',
			'Date'
		];

		yield [
			'Foo',
			'Foo'
		];

	}

}
