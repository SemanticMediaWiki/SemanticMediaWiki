<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\Collator;

/**
 * @covers \SMW\MediaWiki\Collator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CollatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Collator::class,
			Collator::singleton()
		);
	}

	public function testIsIdentical() {

		$collation = $this->getMockBuilder( '\Collation' )
			->disableOriginalConstructor()
			->getMock();

		$collation->expects( $this->exactly( 2 ) )
			->method( 'getSortKey' )
			->will( $this->returnValue( true ) );

		$instance = new Collator(
			$collation
		);

		$this->assertTrue(
			$instance->isIdentical( 'Foo', 'Foo' )
		);
	}

	/**
	 * @dataProvider uppercaseProvider
	 */
	public function testGetFirstLetterOnUppercaseCollation( $text, $firstLetter, $sortKey ) {

		$instance = Collator::singleton( 'uppercase' );

		$this->assertSame(
			$firstLetter,
			$instance->getFirstLetter( $text )
		);

		$this->assertSame(
			$sortKey,
			$instance->getSortKey( $text )
		);
	}

	/**
	 * @dataProvider identityProvider
	 */
	public function testGetFirstLetterOnIdentityCollation( $text, $firstLetter, $sortKey ) {

		$instance = Collator::singleton( 'identity' );

		$this->assertSame(
			$firstLetter,
			$instance->getFirstLetter( $text )
		);

		$this->assertSame(
			$sortKey,
			$instance->getSortKey( $text )
		);
	}

	/**
	 * @dataProvider armorProvider
	 */
	public function testArmor( $collation, $text, $expected ) {

		$instance = Collator::singleton( $collation );

		$this->assertSame(
			$expected,
			$instance->armor( $instance->getSortKey( $text ) )
		);
	}

	public function testArmorOnUCA() {

		if ( !extension_loaded( 'intl' ) ) {
			$this->markTestSkipped( 'Skipping because intl (ICU) is not availabe.' );
		}

		$instance = Collator::singleton( 'uca-default' );
		$text = 'XmlTest';

		$this->assertNotSame(
			$text,
			$instance->armor( $instance->getSortKey( $text ) )
		);
	}

	public function uppercaseProvider() {

		$provider[] = [
			'',
			'',
			''
		];

		$provider[] = [
			'Foo',
			'F',
			'FOO'
		];

		$provider[] = [
			'foo',
			'F',
			'FOO'
		];

		$provider[] = [
			'テスト',
			'テ',
			'テスト'
		];

		$provider[] = [
			'\0テスト',
			'\\',
			'\0テスト'
		];

		return $provider;
	}

	public function identityProvider() {

		$provider[] = [
			'',
			'',
			''
		];

		$provider[] = [
			'Foo',
			'F',
			'Foo'
		];

		$provider[] = [
			'foo',
			'f',
			'foo'
		];

		$provider[] = [
			'テスト',
			'テ',
			'テスト'
		];

		$provider[] = [
			'\0テスト',
			'\\',
			'\0テスト'
		];

		return $provider;
	}

	public function armorProvider() {

		$provider[] = [
			'uppercase',
			'XmlTest',
			'XMLTEST'
		];

		return $provider;
	}

}
