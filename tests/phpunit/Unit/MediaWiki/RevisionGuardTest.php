<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\RevisionGuard;

/**
 * @covers \SMW\MediaWiki\RevisionGuard
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class RevisionGuardTest extends TestCase {

	private $hookContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RevisionGuard::class,
			 new RevisionGuard( $this->getRevisionLookupMock() )
		);
	}

	public function testIsSkippableUpdate() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 42 );

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookContainer(
			$this->hookContainer
		);

		$this->assertIsBool(

			$instance->isSkippableUpdate( $title )
		);
	}

	public function testIsSkippableUpdate_WithID() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->never() )
			->method( 'getLatestRevID' );

		$latestRevID = 1001;

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookContainer(
			$this->hookContainer
		);

		$this->assertIsBool(

			$instance->isSkippableUpdate( $title, $latestRevID )
		);
	}

	public function testGetLatestRevID() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->willReturn( 1001 );

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookContainer(
			$this->hookContainer
		);

		$this->assertEquals(
			1001,
			$instance->getLatestRevID( $title )
		);
	}

	public function testNewRevisionFromTitle() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionLookup = $this->getRevisionLookupMock();
		$revisionLookup->expects( $this->once() )
			->method( 'getRevisionByTitle' );

		$instance = new RevisionGuard( $revisionLookup );

		$instance->setHookContainer( $this->hookContainer );

		$instance->newRevisionFromTitle( $title );
	}

	public function testGetRevision() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( RevisionRecord::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hookContainer->expects( $this->once() )
			->method( 'run' )
			->with( 'SMW::RevisionGuard::ChangeRevision', [ $title, $revision ] );

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookContainer(
			$this->hookContainer
		);

		$this->assertInstanceOf(
			RevisionRecord::class,
			$instance->getRevision( $title, $revision )
		);
	}

	public function testGetFile() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$this->hookContainer->expects( $this->once() )
			->method( 'run' )
			->with( 'SMW::RevisionGuard::ChangeFile', [ $title, $file ] );

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookContainer(
			$this->hookContainer
		);

		$this->assertInstanceOf(
			'\File',
			$instance->getFile( $title, $file )
		);
	}

	private function getRevisionLookupMock() {
		return $this->getMockBuilder( RevisionLookup::class )
			->disableOriginalConstructor()
			->getMock();
	}

}
