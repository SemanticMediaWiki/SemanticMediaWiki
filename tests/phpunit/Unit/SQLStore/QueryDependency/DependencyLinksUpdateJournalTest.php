<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\SQLStore\QueryDependency\DependencyLinksUpdateJournal;
use Title;

/**
 * @covers \SMW\SQLStore\QueryDependency\DependencyLinksUpdateJournal
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DependencyLinksUpdateJournalTest extends \PHPUnit_Framework_TestCase {

	private $cache;
	private $callableUpdate;

	protected function setUp() {
		parent::setUp();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->callableUpdate = $this->getMockBuilder( '\SMW\MediaWiki\Deferred\CallableUpdate' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$queryDependencyLinksStore = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DependencyLinksUpdateJournal::class,
			new DependencyLinksUpdateJournal( $this->cache, $this->callableUpdate )
		);
	}

	public function testMakeKey() {

		$subject = DIWikiPage::newFromText( 'Foo' );
		$title = \Title::newFromText( 'Foo' );

		$this->assertContains(
			'smw:update:qdep',
			DependencyLinksUpdateJournal::makeKey( $subject )
		);

		$this->assertSame(
			DependencyLinksUpdateJournal::makeKey( $title ),
			DependencyLinksUpdateJournal::makeKey( $subject )
		);
	}

	public function testUpdateFromList() {

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->callableUpdate
		);

		$hashList = [
			'Foo#0##'
		];

		$instance->updateFromList( $hashList );
	}

	public function testUpdate() {

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->callableUpdate
		);

		$instance->update( DIWikiPage::newFromText( 'Foo' ) );
	}

	public function testUpdateFromTitle() {

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->callableUpdate
		);

		$instance->update( Title::newFromText( 'Foo' ) );
	}

	public function testHas() {

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) )
			->will( $this->returnValue( true ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->callableUpdate
		);

		$this->assertTrue(
			$instance->has( DIWikiPage::newFromText( 'Foo' ) )
		);
	}

	public function testHasOnDIWikiPageWithSubobject() {

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) )
			->will( $this->returnValue( true ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->callableUpdate
		);

		$this->assertTrue(
			$instance->has( new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar' ) )
		);
	}

	public function testHasFromTitle() {

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) )
			->will( $this->returnValue( true ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->callableUpdate
		);

		$this->assertTrue(
			$instance->has( Title::newFromText( 'Foo' ) )
		);
	}

	public function testDelete() {

		$this->cache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$this->callableUpdate->expects( $this->once() )
			->method( 'setCallback' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			} ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->callableUpdate
		);

		$instance->delete( DIWikiPage::newFromText( 'Foo' ) );
	}

	public function testDeleteFromTitle() {

		$this->cache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$this->callableUpdate->expects( $this->once() )
			->method( 'setCallback' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			} ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->callableUpdate
		);

		$instance->delete( Title::newFromText( 'Foo' ) );
	}

}
