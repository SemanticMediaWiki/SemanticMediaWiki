<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\TitleCreator;
use SMW\Tests\Util\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\TitleCreator
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class TitleCreatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\TitleCreator',
			 new TitleCreator()
		);
	}

	public function testCreateTitleToNotResolveRedirectTarget() {

		$instance = new TitleCreator();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\TitleCreator',
			 $instance->createFromText( __METHOD__ )
		);

		$this->assertInstanceOf(
			'\Title',
			 $instance->createFromText( __METHOD__ )->getTitle()
		);
	}

	public function testCreateTitleTryToResolveRedirectOnMissingPageCreatorThrowsException() {

		$instance = new TitleCreator();

		$this->setExpectedException( 'RuntimeException' );
		$instance->createFromText( __METHOD__ )->findRedirect();
	}

	public function testCreateTitleToResolveRedirectTarget() {

		$instance = $this->createInstanceWithResolvableRedirect();

		$this->assertInstanceOf(
			'\Title',
			 $instance->createFromText( __METHOD__ )->findRedirect()->getTitle()
		);
	}

	public function testCreateTitleWithResolvingRedirectTargetThrowsException() {

		$instance = $this->createInstanceWithUnresolvableRedirect();

		$this->setExpectedException( 'RuntimeException' );
		$instance->createFromText( __METHOD__ )->findRedirect();
	}

	private function createInstanceWithResolvableRedirect() {
		return $this->createInstance( true, true );
	}

	private function createInstanceWithUnresolvableRedirect() {
		return $this->createInstance( false, false );
	}

	private function createInstance( $isRedirect, $isValidRedirectTarget ) {

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( ( $isRedirect ? $this->atLeastOnce() : $this->never() ) )
			->method( 'getRedirectTarget' )
			->will( $this->returnValue( MockTitle::buildMock( 'Ooooooo' ) ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->setConstructorArgs( array( $pageCreator ) )
			->setMethods( array( 'isValidRedirectTarget', 'isRedirect' ) )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isValidRedirectTarget' )
			->will( $this->returnValue( $isValidRedirectTarget ) );

		$instance->expects( $this->at( 0 ) )
			->method( 'isRedirect' )
			->will( $this->returnValue( $isRedirect ) );

		return $instance;
	}

}
