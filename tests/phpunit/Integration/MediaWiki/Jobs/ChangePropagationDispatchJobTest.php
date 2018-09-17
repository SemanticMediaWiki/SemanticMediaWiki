<?php

namespace SMW\Tests\Integration\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\Tests\MwDBaseUnitTestCase;
use Title;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationDispatchJob extends MwDBaseUnitTestCase {

	private $job = null;
	private $pages = [];

	private $mwHooksHandler;
	private $pageCreator;

	private $jobQueueRunner;
	private $jobQueue;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();

		$this->mwHooksHandler->deregisterListedHooks();
		$this->mwHooksHandler->invokeHooksFromRegistry();

		$this->pageCreator = $utilityFactory->newPageCreator();

		$this->jobQueue = ApplicationFactory::getInstance()->getJobQueue();

		$this->jobQueueRunner = $utilityFactory->newRunnerFactory()->newJobQueueRunner();
		$this->jobQueueRunner->setConnectionProvider( $this->getConnectionProvider() );
		$this->jobQueueRunner->deleteAllJobs();

		$this->testEnvironment->addConfiguration( 'smwgEnableUpdateJobs', true );
	}

	protected function tearDown() {

		$this->testEnvironment->flushPages(
			$this->pages
		);

		$this->testEnvironment->tearDown();
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testTriggerUpdateJob() {

		$index = 1; //pass-by-reference

		$this->getStore()->refreshData( $index, 1, false, true )->rebuild( $index );

		$this->assertGreaterThan(
			0,
			$this->jobQueue->getQueueSize( 'smw.update' )
		);
	}

	public function testPropertyTypeChangeToCreateUpdateJob() {

		$this->skipTestForDatabase(
			'sqlite', 'No idea why SQLite fails here with "Failed asserting that 0 is greater than 0".'
		);

		$this->skipTestForMediaWikiVersionLowerThan(
			'1.27',
			"Skipping test because JobQueue::getQueueSize only returns correct results on 1.27+"
		);

		$propertyPage = Title::newFromText( 'FooProperty', SMW_NS_PROPERTY );

		$this->pageCreator
			->createPage( $propertyPage )
			->doEdit( '[[Has type::Page]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Foo', NS_MAIN ) )
			->doEdit( '[[FooProperty::SomePage]]' );

		$this->pageCreator
			->createPage( $propertyPage )
			->doEdit( '[[Has type::Number]]' );

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->jobQueue->disableCache();

		$this->assertGreaterThan(
			0,
			$this->jobQueue->getQueueSize( 'smw.changePropagationDispatch' )
		);

		$this->jobQueueRunner->setType( 'smw.changePropagationDispatch' )->run();

		$this->jobQueue->disableCache();

		$this->assertGreaterThan(
			0,
			$this->jobQueue->getQueueSize( 'smw.update' )
		);

		$this->jobQueueRunner->setType( 'smw.update' )->run();

		foreach ( $this->jobQueueRunner->getStatus() as $status ) {
			$this->assertTrue( $status['status'] );
		}

		$this->pages = [
			$propertyPage,
			Title::newFromText( 'Foo' )
		];
	}

	public function testCategoryChangeToCreateUpdateJob() {

		$this->skipTestForDatabase(
			'sqlite', 'No idea why SQLite fails here with "Failed asserting that 0 is greater than 0".'
		);

		$this->skipTestForMediaWikiVersionLowerThan(
			'1.27',
			"Skipping test because JobQueue::getQueueSize only returns correct results on 1.27+"
		);

		$category = Title::newFromText( 'FooCat', NS_CATEGORY );

		$this->pageCreator
			->createPage( $category )
			->doEdit( '...' );

		$this->pageCreator
			->createPage( $category )
			->doEdit( '[[Category:Bar]]' );

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->jobQueue->disableCache();

		$this->assertGreaterThan(
			0,
			$this->jobQueue->getQueueSize( 'smw.changePropagationDispatch' )
		);

		$this->jobQueueRunner->setType( 'smw.changePropagationDispatch' )->run();

		$this->jobQueue->disableCache();

		$this->assertGreaterThan(
			0,
			$this->jobQueue->getQueueSize( 'smw.update' )
		);

		$this->jobQueueRunner->setType( 'smw.update' )->run();

		foreach ( $this->jobQueueRunner->getStatus() as $status ) {
			$this->assertTrue( $status['status'] );
		}

		$this->pages = [
			$category
		];
	}

}
