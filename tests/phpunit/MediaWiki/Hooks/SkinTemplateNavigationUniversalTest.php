<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\SkinTemplateNavigationUniversal;

/**
 * @covers \SMW\MediaWiki\Hooks\SkinTemplateNavigationUniversal
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SkinTemplateNavigationUniversalTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$links = [];

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\SkinTemplateNavigationUniversal',
			new SkinTemplateNavigationUniversal( $skinTemplate, $links )
		);
	}

	public function testProcess() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->atLeastOnce() )
			->method( 'isAllowed' )
			->willReturn( true );

		$output = $this->getMockBuilder( '\OutputPage' )
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
