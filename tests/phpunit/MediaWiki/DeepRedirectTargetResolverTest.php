<?php

namespace SMW\Tests\MediaWiki;

use RuntimeException;
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
		$title = MockTitle::buildMock('Uuuuuuuuuu');
	
		// Mock WikiPage
		$wikiPage = $this->getMockBuilder('\WikiPage')
			->disableOriginalConstructor()
			->getMock();
	
		$wikiPage->expects($this->any())
			->method('getRedirectTarget')
			->willReturn(MockTitle::buildMock('Ooooooo'));
	
		// Mock PageCreator
		$pageCreator = $this->getMockBuilder('\SMW\MediaWiki\PageCreator')
			->disableOriginalConstructor()
			->getMock();
	
		$pageCreator->expects($this->any())
			->method('createPage')
			->willReturn($wikiPage);
	
		// Mock DeepRedirectTargetResolver
		$instance = $this->getMockBuilder('\SMW\MediaWiki\DeepRedirectTargetResolver')
			->setConstructorArgs([$pageCreator])
			->setMethods(['isValidRedirectTarget', 'isRedirect'])
			->getMock();
	
		$instance->expects($this->any())
			->method('isValidRedirectTarget')
			->willReturn(true);
	
		// Use willReturnOnConsecutiveCalls for isRedirect method
		$instance->expects($this->exactly(2))
			->method('isRedirect')
			->willReturnOnConsecutiveCalls(true, false);
	
		// Perform assertion on the result of findRedirectTargetFor
		$result = $instance->findRedirectTargetFor($title);
		
		$this->assertInstanceOf('\Title', $result);
	}
	
	public function testResolveRedirectTargetThrowsException() {
		$title = MockTitle::buildMock('Uuuuuuuuuu');
	
		$wikiPage = $this->getMockBuilder('\WikiPage')
			->disableOriginalConstructor()
			->getMock();
	
		$wikiPage->expects($this->never())
			->method('getRedirectTarget');
	
		$pageCreator = $this->getMockBuilder('\SMW\MediaWiki\PageCreator')
			->disableOriginalConstructor()
			->getMock();
	
		$pageCreator->expects($this->any())
			->method('createPage')
			->will($this->returnValue($wikiPage));
	
		$instance = $this->getMockBuilder('\SMW\MediaWiki\DeepRedirectTargetResolver')
			->setConstructorArgs([$pageCreator])
			->setMethods(['isValidRedirectTarget', 'isRedirect'])
			->getMock();
	
		$instance->expects($this->atLeastOnce())
			->method('isValidRedirectTarget')
			->will($this->returnValue(false));
	
		// Use withConsecutive() to mock method calls in sequence
		$instance->expects($this->exactly(1))
			->method('isRedirect')
			->withConsecutive([])
			->will($this->returnValue(false));
	
		$this->expectException('RuntimeException');
		$instance->findRedirectTargetFor($title);
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

		$this->expectException( 'RuntimeException' );
		$instance->findRedirectTargetFor( $title );
	}

}
