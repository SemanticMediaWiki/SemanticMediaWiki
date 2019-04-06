<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\RevisionGuard;
use SMW\DIWikiPage;

/**
 * @covers \SMW\MediaWiki\RevisionGuard
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class RevisionGuardTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RevisionGuard::class,
			 new RevisionGuard()
		);
	}

	public function testIsSkippableUpdate() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$this->assertInternalType(
			'boolean',
			RevisionGuard::isSkippableUpdate( $title )
		);
	}

	public function testIsSkippableUpdate_WithID() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->never() )
			->method( 'getLatestRevID' );

		$latestRevID = 1001;

		$this->assertInternalType(
			'boolean',
			RevisionGuard::isSkippableUpdate( $title, $latestRevID )
		);
	}

	public function testGetLatestRevID() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 1001 ) );

		$this->assertEquals(
			1001,
			RevisionGuard::getLatestRevID( $title )
		);
	}

	public function testGetRevision() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\Revision',
			RevisionGuard::getRevision( $title, $revision )
		);
	}

	public function testGetFile() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\File',
			RevisionGuard::getFile( $title, $file )
		);
	}

}
