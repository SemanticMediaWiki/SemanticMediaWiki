<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\NavigationLinksWidget;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\NavigationLinksWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NavigationLinksWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testNavigation() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Ask\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			NavigationLinksWidget::navigationLinks( $title, $urlArgs, 20, false )
		);
	}

	public function testSetMaxInlineLimit() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Ask\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		NavigationLinksWidget::setMaxInlineLimit( 300 );

		$result = NavigationLinksWidget::navigationLinks( $title, $urlArgs, 20, false );

		$this->assertContains(
			'<a rel="nofollow">250</a>',
			$result
		);

		$this->assertNotContains(
			'<a rel="nofollow">500</a>',
			$result
		);
	}

	public function testNavigationLinksOnZeroCountResult() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Ask\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		NavigationLinksWidget::setMaxInlineLimit( 300 );

		$result = NavigationLinksWidget::navigationLinks( $title, $urlArgs, 0, false );

		$this->assertEmpty(
			$result
		);
	}

	public function testOffsetLimit() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Ask\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs->expects( $this->at( 0 ) )
			->method( 'get' )
			->with(	$this->equalTo( 'limit' ) )
			->will( $this->returnValue( 3 ) );

		$urlArgs->expects( $this->at( 1 ) )
			->method( 'get' )
			->with(	$this->equalTo( 'offset' ) )
			->will( $this->returnValue( 10 ) );

		// Previous
		$urlArgs->expects( $this->at( 2 ) )
			->method( 'set' )
			->with(
				$this->equalTo( 'offset' ),
				$this->equalTo( 7 ) );

		$urlArgs->expects( $this->at( 3 ) )
			->method( 'set' )
			->with(
				$this->equalTo( 'limit' ),
				$this->equalTo( 3 ) );

		// Next
		$urlArgs->expects( $this->at( 4 ) )
			->method( 'set' )
			->with(
				$this->equalTo( 'offset' ),
				$this->equalTo( 13 ) );

		$urlArgs->expects( $this->at( 5 ) )
			->method( 'set' )
			->with(
				$this->equalTo( 'limit' ),
				$this->equalTo( 3 ) );

		NavigationLinksWidget::setMaxInlineLimit( 300 );
		NavigationLinksWidget::navigationLinks( $title, $urlArgs, 20, true );
	}

	public function testTopLinks() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertContains(
			'<div class="smw-ask-toplinks"><a href="#options">',
			NavigationLinksWidget::topLinks( $title, [ 'options' ] )
		);

		$this->assertContains(
			'<div class="smw-ask-toplinks">&#160;<a class="float-right">',
			NavigationLinksWidget::topLinks( $title, [ 'empty' ] )
		);
	}

	public function testHiddenTopLinks() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEmpty(
			NavigationLinksWidget::topLinks( $title, [] )
		);
	}

	public function testWrap() {

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$stringValidator->assertThatStringContains(
			[
				'<div id="ask-navinfo">',
				'<div class="smw-ask-cond-info">info</div>',
				'<div class="smw-horizontalrule"',
				'<div class="smw-ask-actions-nav">foo&#160;&#160;&#160;</div></div>'
			],
			NavigationLinksWidget::wrap( 'foo', 'info' )
		);
	}

}
