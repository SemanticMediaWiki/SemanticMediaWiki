<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\Page\RedirectLookup;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\DeepRedirectTargetResolver;
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
		$this->assertInstanceOf(
			DeepRedirectTargetResolver::class,
			 new DeepRedirectTargetResolver( $this->createStub( RedirectLookup::class ) )
		);
	}

	public function testResolveRedirectTarget() {
		$title = MockTitle::buildMock( 'Uuuuuuuuuu' );

		$redirectLookup = $this->createMock( RedirectLookup::class );

		$redirectLookup->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' )
			->willReturn( MockTitle::buildMock( 'Ooooooo' ) );

		$instance = $this->getMockBuilder( DeepRedirectTargetResolver::class )
			->setConstructorArgs( [ $redirectLookup ] )
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

		$redirectLookup = $this->createMock( RedirectLookup::class );

		$redirectLookup->expects( $this->never() )
			->method( 'getRedirectTarget' );

		$instance = $this->getMockBuilder( DeepRedirectTargetResolver::class )
			->setConstructorArgs( [ $redirectLookup ] )
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

		$redirectLookup = $this->createMock( RedirectLookup::class );

		$redirectLookup->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' )
			->willReturn( $title );

		$instance = $this->getMockBuilder( DeepRedirectTargetResolver::class )
			->setConstructorArgs( [ $redirectLookup ] )
			->setMethods( [ 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'isRedirect' )
			->willReturn( true );

		$this->expectException( 'RuntimeException' );
		$instance->findRedirectTargetFor( $title );
	}

}
