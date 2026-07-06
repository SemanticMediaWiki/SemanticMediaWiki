<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use Article;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText;

/**
 * @covers \SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class BeforeDisplayNoArticleTextTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			BeforeDisplayNoArticleText::class,
			new BeforeDisplayNoArticleText()
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testPerformUpdate( $namespace, $text, $expected ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getText' )
			->willReturn( $text );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( $namespace );

		$article = $this->getMockBuilder( Article::class )
			->disableOriginalConstructor()
			->getMock();

		$article->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$instance = new BeforeDisplayNoArticleText();

		$this->assertEquals( $expected, $instance->onBeforeDisplayNoArticleText( $article ) );
	}

	public function titleProvider() {
		$provider = [
			[ SMW_NS_PROPERTY, 'Modification date', false ],
			[ SMW_NS_PROPERTY, 'Foo', true ],
			[ NS_MAIN, 'Modification date', true ],
		];

		return $provider;
	}

}
