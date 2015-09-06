<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Utils\Mock\MockTitle;

use SMW\MediaWiki\Hooks\BeforePageDisplay;

use OutputPage;
use Language;

/**
 * @covers \SMW\MediaWiki\Hooks\BeforePageDisplay
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BeforePageDisplayTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\BeforePageDisplay',
			new BeforePageDisplay( $outputPage, $skin )
		);
	}

	/**
	 * @dataProvider titleDataProvider
	 */
	public function testProcess( $setup, $expected ) {

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$context = new \RequestContext();
		$context->setTitle( $setup['title'] );
		$context->setLanguage( Language::factory( 'en' ) );

		$outputPage = new OutputPage( $context );

		$instance = new BeforePageDisplay( $outputPage, $skin );
		$result   = $instance->process();

		$this->assertInternalType( 'boolean', $result );
		$this->assertTrue( $result );

		$contains = false;

		if ( method_exists( $outputPage, 'getHeadLinksArray' ) ) {
			foreach ( $outputPage->getHeadLinksArray() as $key => $value ) {
				if ( strpos( $value, 'ExportRDF' ) ){
					$contains = true;
					break;
				};
			}
		} else{
			// MW 1.19
			if ( strpos( $outputPage->getHeadLinks(), 'ExportRDF' ) ){
				$contains = true;
			};
		}

		$expected['result'] ? $this->assertTrue( $contains ) : $this->assertFalse( $contains );
	}

	public function titleDataProvider() {

		$language = Language::factory( 'en' );

		#0 Standard title
		$title = MockTitle::buildMockForMainNamespace();

		$title->expects( $this->atLeastOnce() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( false ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$provider[] = array(
			array(
				'title'  => $title
			),
			array(
				'result' => true
			)
		);

		#1 as SpecialPage
		$title = MockTitle::buildMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$provider[] = array(
			array(
				'title'  => $title
			),
			array(
				'result' => false
			)
		);

		return $provider;
	}

}
