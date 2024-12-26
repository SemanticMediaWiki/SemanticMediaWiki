<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText;

/**
 * @covers \SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class BeforeDisplayNoArticleTextTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText',
			new BeforeDisplayNoArticleText( $wikiPage )
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testPerformUpdate( $namespace, $text, $expected ) {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getText' )
			->willReturn( $text );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( $namespace );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$instance = new BeforeDisplayNoArticleText( $wikiPage );

		$this->assertEquals( $expected, $instance->process() );
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
