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

		$urlArgs->expects( $this->any() )
			->method( 'toArray' )
			->will( $this->returnValue( [] ) );

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

		$urlArgs->expects( $this->any() )
			->method( 'toArray' )
			->will( $this->returnValue( [] ) );

		NavigationLinksWidget::setMaxInlineLimit( 300 );

		$result = NavigationLinksWidget::navigationLinks( $title, $urlArgs, 20, false );

		$this->assertContains(
			'class="page-link">250</a>',
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
			->setMethods( [ 'get', 'set' ] )
			->getMock();

		$urlArgs->expects( $this->at( 0 ) )
			->method( 'get' )
			->with(	$this->equalTo( 'limit' ) )
			->will( $this->returnValue( 3 ) );

		$urlArgs->expects( $this->at( 1 ) )
			->method( 'get' )
			->with(	$this->equalTo( 'offset' ) )
			->will( $this->returnValue( 10 ) );

		NavigationLinksWidget::setMaxInlineLimit( 300 );
		NavigationLinksWidget::navigationLinks( $title, $urlArgs, 20, true );
	}

	public function testTopLinks() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertContains(
			'<div id="ask-toplinks" class="smw-ask-toplinks"><span class="float-left"><a href="#options">',
			NavigationLinksWidget::topLinks( $title, [ 'options' ] )
		);

		$this->assertContains(
			'<div id="ask-toplinks" class="smw-ask-toplinks"><span class="float-left"></span>&#160;<span class="float-right">',
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

	public function testBasicLinks() {

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$stringValidator->assertThatStringContains(
			[
				'<div class="smw-ask-actions-nav">foo</div>',
			],
			NavigationLinksWidget::basicLinks( 'foo' )
		);
	}

}
