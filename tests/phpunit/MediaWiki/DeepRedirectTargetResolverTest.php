<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\DeepRedirectTargetResolver;
use SMW\MediaWiki\PageCreator;
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
class DeepRedirectTargetResolverTest extends TestCase {

	public function testCanConstruct() {
		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DeepRedirectTargetResolver::class,
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

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$instance = $this->getMockBuilder( DeepRedirectTargetResolver::class )
			->setConstructorArgs( [ $pageCreator ] )
			->setMethods( [ 'isValidRedirectTarget', 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isValidRedirectTarget' )
			->willReturn( true );

		$isRedirectCallCount = 0;
		$instance->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->willReturnCallback( static function () use ( &$isRedirectCallCount ) {
				return $isRedirectCallCount++ === 0;
			} );

		$this->assertInstanceOf(
			Title::class,
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

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$instance = $this->getMockBuilder( DeepRedirectTargetResolver::class )
			->setConstructorArgs( [ $pageCreator ] )
			->setMethods( [ 'isValidRedirectTarget', 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isValidRedirectTarget' )
			->willReturn( false );

		$instance->expects( $this->once() )
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

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$instance = $this->getMockBuilder( DeepRedirectTargetResolver::class )
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
