<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\NavigationLinksWidget;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\NavigationLinksWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NavigationLinksWidgetTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testNavigation() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs->expects( $this->any() )
			->method( 'toArray' )
			->willReturn( [] );

		$this->assertIsString(

			NavigationLinksWidget::navigationLinks( $title, $urlArgs, 20, false )
		);
	}

	public function testSetMaxInlineLimit() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs->expects( $this->any() )
			->method( 'toArray' )
			->willReturn( [] );

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

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
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

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get', 'set' ] )
			->getMock();

		$urlArgs->expects( $this->at( 0 ) )
			->method( 'get' )
			->with(	'limit' )
			->willReturn( 3 );

		$urlArgs->expects( $this->at( 1 ) )
			->method( 'get' )
			->with(	'offset' )
			->willReturn( 10 );

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
