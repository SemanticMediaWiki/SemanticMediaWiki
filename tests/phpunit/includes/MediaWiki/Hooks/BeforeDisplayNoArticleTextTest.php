<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText;

/**
 * @covers \SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class BeforeDisplayNoArticleTextTest extends \PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $namespace ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$instance = new BeforeDisplayNoArticleText( $wikiPage );

		$this->assertEquals( $expected, $instance->process() );
	}

	public function titleProvider() {

		$provider = array(
			array( SMW_NS_PROPERTY, 'Modification date', false ),
			array( SMW_NS_PROPERTY, 'Foo', true ),
			array( NS_MAIN, 'Modification date', true ),
		);

		return $provider;
	}

}
