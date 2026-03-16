<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
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
		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$links = [];

		$this->assertInstanceOf(
			SkinTemplateNavigationUniversal::class,
			new SkinTemplateNavigationUniversal( $skinTemplate, $links )
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

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
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

		$links = [];

		$instance = new SkinTemplateNavigationUniversal( $skinTemplate, $links );
		$instance->process();

		$this->assertArrayHasKey( 'purge', $links['actions'] );
	}

}
