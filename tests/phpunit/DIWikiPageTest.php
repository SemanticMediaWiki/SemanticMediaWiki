<?php

namespace SMW\Tests;

use SMW\DIWikiPage;

/**
 * @covers \SMW\DIWikiPage
 * @covers SMWDataItem
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWDataItems
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DIWikiPageTest extends DataItemTest {

	/**
	 * @see DataItemTest::getClass
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getClass() {
		return 'SMW\DIWikiPage';
	}

	/**
	 * @see DataItemTest::constructorProvider
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function constructorProvider() {
		return [
			[ 'Foo', NS_MAIN, '' ],
			[ 'Foo_Bar', NS_MAIN, '' ],
			[ 'Foo_Bar_Baz', NS_MAIN, '', 'spam' ],
		];
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetTitleAndNewFromTitleRoundrtip( DIWikiPage $di ) {
		$newDi = DIWikiPage::newFromTitle( $di->getTitle() );
		$this->assertTrue( $newDi->equals( $di ) );
	}

	/**
	 * @dataProvider sortKeyProvider
	 */
	public function testSortKeyRoundtrip( $title, $sortkey, $expected ) {

		$instance = new DIWikiPage( $title, NS_MAIN );

		$instance->setSortKey( $sortkey );

		$this->assertEquals(
			$expected,
			$instance->getSortKey()
		);
	}

	/**
	 * @dataProvider subEntityProvider
	 */
	public function testIsSubEntityOf( $dbKey, $subobjectName, $subEntity, $expected ) {

		$instance = new DIWikiPage( $dbKey, NS_MAIN, '', $subobjectName );

		$this->assertEquals(
			$expected,
			$instance->isSubEntityOf( $subEntity )
		);
	}

	public function testInNamespace() {

		$instance = new DIWikiPage( 'Foo', NS_HELP );

		$this->assertFalse(
			$instance->inNamespace( SMW_NS_PROPERTY )
		);

		$this->assertTrue(
			$instance->inNamespace( NS_HELP )
		);
	}

	public function testInNamespace_EmptyDBKey() {

		$instance = new DIWikiPage( '', NS_HELP );

		$this->assertFalse(
			$instance->inNamespace( NS_HELP )
		);
	}

	public function testDoUnserialize() {

		$expected = new DIWikiPage( 'Foo', 0 , '', '' );

		$this->assertEquals(
			$expected,
			DIWikiPage::doUnserialize( 'Foo#0##' )
		);

		$this->assertEquals(
			$expected,
			DIWikiPage::doUnserialize( 'Foo#0##' )
		);
	}

	public function sortKeyProvider() {

		$provider[] = [
			'Some_title',
			null,
			'Some title'
		];

		$provider[] = [
			'Some_title',
			'',
			'Some title'
		];

		$provider[] = [
			'Some_title',
			'abc',
			'abc'
		];

		$provider[] = [
			'Some_title',
			'abc_def',
			'abc def'
		];

		return $provider;
	}

	public function subEntityProvider() {

		yield 'empty dbkey' => [
			'',
			'_ML-foo',
			SMW_SUBENTITY_MONOLINGUAL,
			false
		];

		yield 'empty prefix' => [
			'FOO',
			'_ML-foo',
			'',
			false
		];

		yield SMW_SUBENTITY_MONOLINGUAL => [
			'FOO',
			'_ML-foo',
			SMW_SUBENTITY_MONOLINGUAL,
			true
		];

		yield SMW_SUBENTITY_REFERENCE => [
			'FOO',
			'_REF-foo',
			SMW_SUBENTITY_REFERENCE,
			true
		];

		yield SMW_SUBENTITY_QUERY => [
			'FOO',
			'_QUERY-foo',
			SMW_SUBENTITY_QUERY,
			true
		];

		yield SMW_SUBENTITY_ERROR => [
			'FOO',
			'_ERR-foo',
			SMW_SUBENTITY_ERROR,
			true
		];
	}

}
