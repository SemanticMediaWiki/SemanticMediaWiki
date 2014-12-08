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
		return array(
			array( 'Foo', NS_MAIN, '' ),
			array( 'Foo_Bar', NS_MAIN, '' ),
			array( 'Foo_Bar_Baz', NS_MAIN, '', 'spam' ),
		);
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

	public function sortKeyProvider() {

		$provider[] = array(
			'Some_title',
			null,
			'Some title'
		);

		$provider[] = array(
			'Some_title',
			'',
			'Some title'
		);

		$provider[] = array(
			'Some_title',
			'abc',
			'abc'
		);

		$provider[] = array(
			'Some_title',
			'abc_def',
			'abc def'
		);

		return $provider;
	}

}
