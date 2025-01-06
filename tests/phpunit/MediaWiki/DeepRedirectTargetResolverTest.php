<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\DeepRedirectTargetResolver;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\DeepRedirectTargetResolver
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class DeepRedirectTargetResolverTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\DeepRedirectTargetResolver',
			 new DeepRedirectTargetResolver( $pageCreator )
		);
	}

	public function testResolveRedirectTarget() {
		$title = MockTitle::buildMock( 'Uuuuuuuuuu' );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' )
			->willReturn( MockTitle::buildMock( 'Ooooooo' ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\DeepRedirectTargetResolver' )
			->setConstructorArgs( [ $pageCreator ] )
			->setMethods( [ 'isValidRedirectTarget', 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isValidRedirectTarget' )
			->willReturn( true );

		$instance->expects( $this->at( 0 ) )
			->method( 'isRedirect' )
			->willReturn( true );

		$this->assertInstanceOf(
			'\Title',
			 $instance->findRedirectTargetFor( $title )
		);
	}

	public function testResolveRedirectTargetThrowsException() {
		$title = MockTitle::buildMock( 'Uuuuuuuuuu' );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->never() )
			->method( 'getRedirectTarget' );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\DeepRedirectTargetResolver' )
			->setConstructorArgs( [ $pageCreator ] )
			->setMethods( [ 'isValidRedirectTarget', 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isValidRedirectTarget' )
			->willReturn( false );

		$instance->expects( $this->at( 0 ) )
			->method( 'isRedirect' )
			->willReturn( false );

		$this->expectException( 'RuntimeException' );
		$instance->findRedirectTargetFor( $title );
	}

	public function testTryToResolveCircularRedirectThrowsException() {
		$title = MockTitle::buildMock( 'Uuuuuuuuuu' );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' )
			->willReturn( $title );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\DeepRedirectTargetResolver' )
			->setConstructorArgs( [ $pageCreator ] )
			->setMethods( [ 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'isRedirect' )
			->willReturn( true );

		$this->expectException( 'RuntimeException' );
		$instance->findRedirectTargetFor( $title );
	}

}
