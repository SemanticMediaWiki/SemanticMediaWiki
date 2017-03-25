<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\ByLanguageCollationMapper;

/**
 * @covers \SMW\MediaWiki\ByLanguageCollationMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ByLanguageCollationMapperTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\ByLanguageCollationMapper',
			new ByLanguageCollationMapper( 'Foo' )
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\ByLanguageCollationMapper',
			ByLanguageCollationMapper::getInstance()
		);

		ByLanguageCollationMapper::clear();
	}

	/**
	 * @dataProvider letterForUppercaseProvider
	 */
	public function testfindFirstLetterForCategoryByUppercaseCollation( $category, $expected ) {

		$instance = new ByLanguageCollationMapper( 'uppercase' );

		$this->assertSame(
			$expected,
			$instance->findFirstLetterForCategory( $category )
		);
	}

	/**
	 * @dataProvider letterForIdentityProvider
	 */
	public function testfindFirstLetterForCategoryByIdentityCollation( $category, $expected ) {

		$instance = new ByLanguageCollationMapper( 'identity' );

		$this->assertSame(
			$expected,
			$instance->findFirstLetterForCategory( $category )
		);
	}

	/**
	 * @dataProvider letterForIdentityProvider
	 */
	public function testfindFirstLetterForCategoryByUnknownCollation( $category, $expected ) {

		$instance = new ByLanguageCollationMapper( 'foo' );

		$this->assertSame(
			$expected,
			$instance->findFirstLetterForCategory( $category )
		);
	}

	public function letterForUppercaseProvider() {

		$provider[] = [
			'',
			''
		];

		$provider[] = [
			'Foo',
			'F'
		];

		$provider[] = [
			'foo',
			'F'
		];

		$provider[] = [
			'テスト',
			'テ'
		];

		$provider[] = [
			'\0テスト',
			'\\'
		];

		return $provider;
	}

	public function letterForIdentityProvider() {

		$provider[] = [
			'',
			''
		];

		$provider[] = [
			'Foo',
			'F'
		];

		$provider[] = [
			'foo',
			'f'
		];

		$provider[] = [
			'テスト',
			'テ'
		];

		$provider[] = [
			'\0テスト',
			'\\'
		];

		return $provider;
	}

}
