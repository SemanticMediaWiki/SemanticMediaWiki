<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SkinTemplate;
use SMW\MediaWiki\Hooks\PersonalUrls;
use SMW\MediaWiki\Hooks\SkinTemplateNavigationUniversal;

/**
 * @covers \SMW\MediaWiki\Hooks\SkinTemplateNavigationUniversal
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class SkinTemplateNavigationUniversalTest extends TestCase {

	public function testCanConstruct() {
		$personalUrls = $this->getMockBuilder( PersonalUrls::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SkinTemplateNavigationUniversal::class,
			new SkinTemplateNavigationUniversal( $personalUrls )
		);
	}

	public function testProcess() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( Message::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->atLeastOnce() )
			->method( 'isAllowed' )
			->willReturn( true );

		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate = $this->getMockBuilder( SkinTemplate::class )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $output );

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getUser' )
			->willReturn( $user );

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'msg' )
			->willReturn( $message );

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$personalUrls = $this->getMockBuilder( PersonalUrls::class )
			->disableOriginalConstructor()
			->getMock();

		$links = [];

		$instance = new SkinTemplateNavigationUniversal( $personalUrls );
		$instance->onSkinTemplateNavigation__Universal( $skinTemplate, $links );

		$this->assertArrayHasKey( 'purge', $links['actions'] );
	}

	public function testJobQueueWatchlistIsDelegatedToNotificationsMenu() {
		$title = $this->createMock( Title::class );

		$user = $this->createMock( User::class );
		$user->method( 'isAllowed' )->willReturn( false );

		$skinTemplate = $this->createMock( SkinTemplate::class );
		$skinTemplate->method( 'getUser' )->willReturn( $user );
		$skinTemplate->method( 'getTitle' )->willReturn( $title );

		$personalUrls = $this->createMock( PersonalUrls::class );
		$personalUrls->expects( $this->once() )
			->method( 'onPersonalUrls' )
			->with( [], $title, $skinTemplate );

		$links = [ 'notifications' => [] ];

		$instance = new SkinTemplateNavigationUniversal( $personalUrls );
		$instance->onSkinTemplateNavigation__Universal( $skinTemplate, $links );
	}

	public function testJobQueueWatchlistIsSkippedWithoutNotificationsMenu() {
		$user = $this->createMock( User::class );
		$user->method( 'isAllowed' )->willReturn( false );

		$skinTemplate = $this->createMock( SkinTemplate::class );
		$skinTemplate->method( 'getUser' )->willReturn( $user );

		$personalUrls = $this->createMock( PersonalUrls::class );
		$personalUrls->expects( $this->never() )
			->method( 'onPersonalUrls' );

		$links = [];

		$instance = new SkinTemplateNavigationUniversal( $personalUrls );
		$instance->onSkinTemplateNavigation__Universal( $skinTemplate, $links );
	}

}
