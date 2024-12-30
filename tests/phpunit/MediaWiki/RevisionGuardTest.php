<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\RevisionGuard;
use SMW\DIWikiPage;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\RevisionGuard
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class RevisionGuardTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $hookDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
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
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 42 );

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertIsBool(

			$instance->isSkippableUpdate( $title )
		);
	}

	public function testIsSkippableUpdate_WithID() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->never() )
			->method( 'getLatestRevID' );

		$latestRevID = 1001;

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertIsBool(

			$instance->isSkippableUpdate( $title, $latestRevID )
		);
	}

	public function testGetLatestRevID() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->willReturn( 1001 );

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertEquals(
			1001,
			$instance->getLatestRevID( $title )
		);
	}

	public function testNewRevisionFromTitle() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$revisionLookup = $this->getRevisionLookupMock();
		$revisionLookup->expects( $this->once() )
			->method( 'getRevisionByTitle' );

		$instance = new RevisionGuard( $revisionLookup );

		$instance->setHookDispatcher( $this->hookDispatcher );

		$instance->newRevisionFromTitle( $title );
	}

	public function testGetRevision() {
		$this->hookDispatcher->expects( $this->once() )
			->method( 'onChangeRevision' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertInstanceOf(
			'\MediaWiki\Revision\RevisionRecord',
			$instance->getRevision( $title, $revision )
		);
	}

	public function testGetFile() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$this->hookDispatcher->expects( $this->once() )
			->method( 'onChangeFile' )
			->with(
				$title,
				$file );

		$instance = new RevisionGuard( $this->getRevisionLookupMock() );

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertInstanceOf(
			'\File',
			$instance->getFile( $title, $file )
		);
	}

	private function getRevisionLookupMock() {
		return $this->getMockBuilder( '\MediaWiki\Revision\RevisionLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

}
