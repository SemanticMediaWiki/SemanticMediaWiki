<?php

namespace SMW\Test;

use SMW\Highlighter;

/**
 * @covers \SMW\Highlighter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class HighlighterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getTypeDataProvider
	 */
	public function testCanConstruct( $type ) {

		$this->assertInstanceOf(
			'\SMW\Highlighter',
			Highlighter::factory( $type )
		);
	}

	/**
	 * @dataProvider getTypeDataProvider
	 */
	public function testGetTypeId( $type, $expected ) {
		$results = Highlighter::getTypeId( $type );

		$this->assertInternalType(
			'integer',
			$results
		);

		$this->assertEquals(
			$expected,
			$results
		);
	}

	public function testDecode() {

		$this->assertEquals(
			'&<> ',
			Highlighter::decode( '&amp;&lt;&gt;&#160;<nowiki></nowiki>' )
		);
	}

	/**
	 * @dataProvider getTypeDataProvider
	 */
	public function testGetHtml( $type ) {

		$instance = Highlighter::factory( $type );

		$instance->setContent( [
			'title' => 'Foo'
		] );

		// Check without caption/content set
		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);

		$instance->setContent( [
			'caption' => '123',
			'content' => 'ABC',
		] );

		// Check with caption/content set
		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testHasHighlighterClass() {

		$instance = Highlighter::factory(
			Highlighter::TYPE_WARNING
		);

		$instance->setContent( [
			'title' => 'Foo'
		] );

		$this->assertTrue(
			Highlighter::hasHighlighterClass( $instance->getHtml(), 'warning' )
		);
	}

	public function getTypeDataProvider() {
		return [
			[ '' , Highlighter::TYPE_NOTYPE ],
			[ 'property', Highlighter::TYPE_PROPERTY ],
			[ 'text', Highlighter::TYPE_TEXT ],
			[ 'info', Highlighter::TYPE_INFO ],
			[ 'help', Highlighter::TYPE_HELP ],
			[ 'service', Highlighter::TYPE_SERVICE ],
			[ 'quantity', Highlighter::TYPE_QUANTITY ],
			[ 'note', Highlighter::TYPE_NOTE ],
			[ 'warning', Highlighter::TYPE_WARNING ],
			[ 'error', Highlighter::TYPE_ERROR ],
			[ 'PrOpErTy', Highlighter::TYPE_PROPERTY ],
			[ 'バカなテスト', Highlighter::TYPE_NOTYPE ],
			[ '<span>Something that should not work</span>', Highlighter::TYPE_NOTYPE ],
			[ Highlighter::TYPE_PROPERTY, Highlighter::TYPE_NOTYPE ]
		];
	}

}
