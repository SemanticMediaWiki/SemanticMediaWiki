<?php

namespace SMW\Tests\Unit\MediaWiki;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Collator;

/**
 * @covers \SMW\MediaWiki\Collator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CollatorTest extends TestCase {

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
			->willReturn( true );

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
			$this->markTestSkipped( 'Skipping because intl (ICU) is not available.' );
		}

		$instance = Collator::singleton( 'uca-default' );
		$text = 'XmlTest';

		$this->assertNotSame(
			$text,
			$instance->armor( $instance->getSortKey( $text ) )
		);
	}

	/**
	 * The raw ICU sort key of a uca-* collation is a binary blob that is not
	 * valid UTF-8, so it cannot be stored in a Postgres TEXT column. The
	 * armored form must be storable (valid UTF-8, no NUL bytes).
	 *
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7049
	 */
	public function testArmoredSortKeyOnUCAIsStorable() {
		if ( !extension_loaded( 'intl' ) ) {
			$this->markTestSkipped( 'Skipping because intl (ICU) is not available.' );
		}

		$instance = Collator::singleton( 'uca-default' );

		// Reproduces #7049: for a uca-* collation ICU emits a binary sort key
		// that is not valid UTF-8 (holds for Latin text across ICU versions).
		// The armored value asserted below is what makes it storable.
		$this->assertFalse(
			mb_check_encoding( $instance->getSortKey( 'Has type' ), 'UTF-8' )
		);

		$armored = $instance->armoredSortKey( 'Has type' );

		$this->assertTrue( mb_check_encoding( $armored, 'UTF-8' ) );
		$this->assertStringNotContainsString( "\0", $armored );
	}

	/**
	 * Armoring preserves the bitwise order of the raw sort keys, so a DB-side
	 * ORDER BY on the stored value keeps collation order.
	 */
	public function testArmoredSortKeyPreservesOrderOnUCA() {
		if ( !extension_loaded( 'intl' ) ) {
			$this->markTestSkipped( 'Skipping because intl (ICU) is not available.' );
		}

		$instance = Collator::singleton( 'uca-default' );

		foreach ( [ [ 'apple', 'banana' ], [ 'Zoo', 'apple' ], [ 'aa', 'ab' ] ] as [ $a, $b ] ) {
			$rawCmp = strcmp( $instance->getSortKey( $a ), $instance->getSortKey( $b ) ) <=> 0;
			$armoredCmp = strcmp( $instance->armoredSortKey( $a ), $instance->armoredSortKey( $b ) ) <=> 0;

			$this->assertSame( $rawCmp, $armoredCmp, "order mismatch for '$a' vs '$b'" );
		}
	}

	/**
	 * For non-binary collations (identity/uppercase/numeric) the sort key is
	 * already valid text, so armoring is a no-op and the value is unchanged.
	 */
	public function testArmoredSortKeyIsPlainForIdentityCollation() {
		$instance = Collator::singleton( 'identity' );

		$this->assertSame(
			'Foo bar',
			$instance->armoredSortKey( 'Foo bar' )
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
