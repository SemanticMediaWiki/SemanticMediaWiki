<?php

namespace SMW\Tests\Unit\Formatters;

use PHPUnit\Framework\TestCase;
use SMW\Formatters\Highlighter;

/**
 * @covers \SMW\Formatters\Highlighter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class HighlighterTest extends TestCase {

	/**
	 * @dataProvider getTypeDataProvider
	 */
	public function testCanConstruct( $type ) {
		$this->assertInstanceOf(
			Highlighter::class,
			Highlighter::factory( $type )
		);
	}

	/**
	 * @dataProvider getTypeDataProvider
	 */
	public function testGetTypeId( $type, $expected ) {
		$results = Highlighter::getTypeId( $type );

		$this->assertIsInt(

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
		$this->assertIsString(

			$instance->getHtml()
		);

		$instance->setContent( [
			'caption' => '123',
			'content' => 'ABC',
		] );

		// Check with caption/content set
		$this->assertIsString(

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

	public function testOversizedTitleIsTruncatedWithAnEllipsis() {
		$this->assertSame(
			str_repeat( 'a', 1022 ) . ' …',
			$this->titleAttributeOf( $this->htmlForContent( str_repeat( 'a', 2000 ) ) )
		);
	}

	public function testOversizedTitleIsTruncatedByCharactersNotBytes() {
		$this->assertSame(
			str_repeat( '日本語 ', 255 ) . '日本' . ' …',
			$this->titleAttributeOf( $this->htmlForContent( str_repeat( '日本語 ', 500 ) ) )
		);
	}

	public function testTitleAttributeKeepsShortContentIntact() {
		$content = 'Property Foo has an invalid value.';

		$this->assertSame(
			$content,
			$this->titleAttributeOf( $this->htmlForContent( $content ) )
		);
	}

	public function testOversizedContentRemainsAvailableForTheTooltip() {
		$content = str_repeat( 'lorem ipsum ', 50000 );

		$this->assertStringContainsString(
			'<span class="smwttcontent">' . $content . '</span>',
			$this->htmlForContent( $content )
		);
	}

	public function testDataContentKeepsTheTooltipTextNodeEmpty() {
		$instance = Highlighter::factory( Highlighter::TYPE_PROPERTY );

		$instance->setContent( [
			'caption' => 'Foo',
			'content' => 'Plain fallback text',
			'data-content' => '<a href="http://example.org/">foo</a>'
		] );

		$html = $instance->getHtml();

		$this->assertStringContainsString(
			'data-content="&lt;a href=&quot;http://example.org/&quot;&gt;foo&lt;/a&gt;"',
			$html
		);

		$this->assertStringContainsString(
			'<span class="smwttcontent"></span>',
			$html
		);
	}

	private function htmlForContent( string $content ): string {
		$instance = Highlighter::factory( Highlighter::TYPE_TEXT );

		$instance->setContent( [
			'caption' => ' … ',
			'content' => $content
		] );

		return $instance->getHtml();
	}

	private function titleAttributeOf( string $html ): string {
		preg_match( '/ title="([^"]*)"/', $html, $matches );

		return $matches[1] ?? '';
	}

	public function getTypeDataProvider() {
		return [
			[ '', Highlighter::TYPE_NOTYPE ],
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
