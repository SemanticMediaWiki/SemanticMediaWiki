<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\SQLStore\QueryDependency\DependencyLinksUpdateJournal;
use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
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
	private $deferredCallableUpdate;

	protected function setUp() {
		parent::setUp();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->deferredCallableUpdate = $this->getMockBuilder( '\SMW\Updater\DeferredCallableUpdate' )
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
			new DependencyLinksUpdateJournal( $this->cache, $this->deferredCallableUpdate )
		);
	}

	public function testUpdateFromList() {

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->deferredCallableUpdate
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
			$this->deferredCallableUpdate
		);

		$instance->update( DIWikiPage::newFromText( 'Foo' ) );
	}

	public function testUpdateFromTitle() {

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->deferredCallableUpdate
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
			$this->deferredCallableUpdate
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
			$this->deferredCallableUpdate
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
			$this->deferredCallableUpdate
		);

		$this->assertTrue(
			$instance->has( Title::newFromText( 'Foo' ) )
		);
	}

	public function testDelete() {

		$this->cache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$this->deferredCallableUpdate->expects( $this->once() )
			->method( 'setCallback' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			} ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->deferredCallableUpdate
		);

		$instance->delete( DIWikiPage::newFromText( 'Foo' ) );
	}

	public function testDeleteFromTitle() {

		$this->cache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:update:qdep:7ab9f795d4ce7c20051947ede72baec3' ) );

		$this->deferredCallableUpdate->expects( $this->once() )
			->method( 'setCallback' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			} ) );

		$instance = new DependencyLinksUpdateJournal(
			$this->cache,
			$this->deferredCallableUpdate
		);

		$instance->delete( Title::newFromText( 'Foo' ) );
	}

}
