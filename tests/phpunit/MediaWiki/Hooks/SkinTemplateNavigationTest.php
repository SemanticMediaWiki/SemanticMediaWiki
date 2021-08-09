<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\SkinTemplateNavigation;

/**
 * @covers \SMW\MediaWiki\Hooks\SkinTemplateNavigation
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SkinTemplateNavigationTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$links = [];

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\SkinTemplateNavigation',
			new SkinTemplateNavigation( $skinTemplate, $links )
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
			->will( $this->returnValue( true ) );
		
		$output = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->will( $this->returnValue( $output ) );
			
		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'msg' )
			->will( $this->returnValue( $message ) );

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$links = [];

		$instance = new SkinTemplateNavigation( $skinTemplate, $links );
		$instance->process();

		$this->assertArrayHasKey( 'purge', $links['actions'] );
	}

}
