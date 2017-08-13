<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\NavigationLinksWidget;

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

		$this->assertInternalType(
			'string',
			NavigationLinksWidget::navigationLinks( $title, 100, 0, 20, false, [] )
		);
	}

	public function testSetMaxInlineLimit() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		NavigationLinksWidget::setMaxInlineLimit( 300 );

		$result = NavigationLinksWidget::navigationLinks( $title, 100, 0, 20, false, [] );

		$this->assertContains(
			'<a rel="nofollow">250</a>',
			$result
		);

		$this->assertNotContains(
			'<a rel="nofollow">500</a>',
			$result
		);
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

}
