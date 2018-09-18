<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\DeepRedirectTargetResolver;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\DeepRedirectTargetResolver
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class DeepRedirectTargetResolverTest extends \PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( MockTitle::buildMock( 'Ooooooo' ) ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\DeepRedirectTargetResolver' )
			->setConstructorArgs( [ $pageCreator ] )
			->setMethods( [ 'isValidRedirectTarget', 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isValidRedirectTarget' )
			->will( $this->returnValue( true ) );

		$instance->expects( $this->at( 0 ) )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

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
			->will( $this->returnValue( $wikiPage ) );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\DeepRedirectTargetResolver' )
			->setConstructorArgs( [ $pageCreator ] )
			->setMethods( [ 'isValidRedirectTarget', 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isValidRedirectTarget' )
			->will( $this->returnValue( false ) );

		$instance->expects( $this->at( 0 ) )
			->method( 'isRedirect' )
			->will( $this->returnValue( false ) );

		$this->setExpectedException( 'RuntimeException' );
		$instance->findRedirectTargetFor( $title );
	}

	public function testTryToResolveCircularRedirectThrowsException() {

		$title = MockTitle::buildMock( 'Uuuuuuuuuu' );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' )
			->will( $this->returnValue( $title ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\DeepRedirectTargetResolver' )
			->setConstructorArgs( [ $pageCreator ] )
			->setMethods( [ 'isRedirect' ] )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$this->setExpectedException( 'RuntimeException' );
		$instance->findRedirectTargetFor( $title );
	}

}
