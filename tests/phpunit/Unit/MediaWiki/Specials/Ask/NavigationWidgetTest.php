<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\NavigationWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\NavigationWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NavigationWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testNavigation() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			NavigationWidget::navigation( $title, 100, 0, 20, false, [] )
		);
	}

	public function testSetMaxInlineLimit() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		NavigationWidget::setMaxInlineLimit( 300 );

		$result = NavigationWidget::navigation( $title, 100, 0, 20, false, [] );

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
			'<div class="smw-ask-toplinks">',
			NavigationWidget::topLinks( $title )
		);
	}

	public function testHiddenTopLinks() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEmpty(
			NavigationWidget::topLinks( $title, true )
		);
	}

}
