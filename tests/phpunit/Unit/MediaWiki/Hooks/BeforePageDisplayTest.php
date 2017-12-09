<?php

namespace SMW\Tests\MediaWiki\Hooks;

use Language;
use OutputPage;
use SMW\MediaWiki\Hooks\BeforePageDisplay;
use SMW\Tests\Utils\Mock\MockTitle;

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

		$expected = $expected['result'] ? $this->atLeastOnce() : $this->never();

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $setup['title'] ) );

		$outputPage->expects( $expected )
			->method( 'addLink' );

		$instance = new BeforePageDisplay( $outputPage, $skin );

		$this->assertTrue(
			$instance->process()
		);
	}

	public function titleDataProvider() {

		#0 Standard title
		$title = MockTitle::buildMockForMainNamespace();

		$title->expects( $this->atLeastOnce() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( false ) );

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
