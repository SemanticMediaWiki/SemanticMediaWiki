<?php

namespace SMW\Test;

use SMW\Highlighter;

/**
 * Tests for the Highlighter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\Highlighter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class HighlighterTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Highlighter';
	}

	/**
	 * @test Highlighter::factory
	 * @dataProvider getTypeDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $type
	 */
	public function testFactory( $type ) {
		$instance = Highlighter::factory( $type );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test Highlighter::getTypeId
	 * @dataProvider getTypeDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $type
	 * @param $expected
	 */
	public function testGetTypeId( $type, $expected ) {
		$results = Highlighter::getTypeId( $type );

		$this->assertInternalType( 'integer', $results );
		$this->assertEquals( $expected, $results );
	}

	/**
	 * @test Highlighter::getHtml
	 * @dataProvider getTypeDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $type
	 */
	public function testGetHtml( $type ) {

		$instance = Highlighter::factory( $type );
		$title    = $this->newTitle();

		$instance->setContent( array(
			'title' => $title->getFullText()
		) );

		// Check without caption/content set
		$this->assertInternalType( 'string', $instance->getHtml() );

		$instance->setContent( array(
			'caption' => '123',
			'content' => 'ABC',
		) );

		// Check with caption/content set
		$this->assertInternalType( 'string', $instance->getHtml() );
	}

	/**
	 * Provides test sample
	 *
	 * @return array
	 */
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
