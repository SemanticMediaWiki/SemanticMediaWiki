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

	/**
	 * @dataProvider getTypeDataProvider
	 */
	public function testGetHtml( $type ) {

		$instance = Highlighter::factory( $type );

		$instance->setContent( array(
			'title' => 'Foo'
		) );

		// Check without caption/content set
		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);

		$instance->setContent( array(
			'caption' => '123',
			'content' => 'ABC',
		) );

		// Check with caption/content set
		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function getTypeDataProvider() {
		return array(
			array( '' , Highlighter::TYPE_NOTYPE ),
			array( 'property', Highlighter::TYPE_PROPERTY ),
			array( 'text', Highlighter::TYPE_TEXT ),
			array( 'info', Highlighter::TYPE_INFO ),
			array( 'help', Highlighter::TYPE_HELP ),
			array( 'service', Highlighter::TYPE_SERVICE ),
			array( 'quantity', Highlighter::TYPE_QUANTITY ),
			array( 'note', Highlighter::TYPE_NOTE ),
			array( 'warning', Highlighter::TYPE_WARNING ),
			array( 'PrOpErTy', Highlighter::TYPE_PROPERTY ),
			array( 'バカなテスト', Highlighter::TYPE_NOTYPE ),
			array( '<span>Something that should not work</span>', Highlighter::TYPE_NOTYPE ),
			array( Highlighter::TYPE_PROPERTY, Highlighter::TYPE_NOTYPE )
		);
	}

}
