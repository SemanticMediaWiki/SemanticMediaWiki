<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\TypesValue;

/**
 * @covers \SMW\DataValues\TypesValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TypesValueTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TypesValue::class,
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
