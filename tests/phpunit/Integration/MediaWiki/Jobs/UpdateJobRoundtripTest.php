<?php

namespace SMW\Tests\Integration\MediaWiki\Jobs;

use Job;
use SMW\ApplicationFactory;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class UpdateJobRoundtripTest extends MwDBaseUnitTestCase {

	private $job = null;
	private $applicationFactory;

	private $deletePoolOfPages = [];
	private $runnerFactory;

	private $mwHooksHandler;
	private $semanticDataValidator;

	private $pageDeleter;
	private $pageCreator;

	private $jobQueueRunner;
	private $jobQueue;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();
		$this->pageDeleter = $utilityFactory->newPageDeleter();
		$this->pageCreator = $utilityFactory->newPageCreator();

		$this->applicationFactory = ApplicationFactory::getInstance();

		// FIXME Because of SQLStore::Writer::changeTitle
		$GLOBALS['smwgEnableUpdateJobs'] = true;

		$settings = [
			'smwgEnableUpdateJobs' => true
		];

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		$this->jobQueue = $this->applicationFactory->getJobQueue();

		$this->jobQueueRunner = $utilityFactory->newRunnerFactory()->newJobQueueRunner();
		$this->jobQueueRunner->setConnectionProvider( $this->getConnectionProvider() );
		$this->jobQueueRunner->deleteAllJobs();
	}

	protected function tearDown() {

		$this->pageDeleter->doDeletePoolOfPages(
			$this->deletePoolOfPages
		);

		$this->applicationFactory->clear();
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testPageMoveTriggersUpdateJob() {

		$oldTitle = Title::newFromText( __METHOD__ . '-old' );
		$newTitle = Title::newFromText( __METHOD__ . '-new' );

		$this->pageCreator
			->createPage( $oldTitle )
			->doEdit( '[[Has jobqueue test::UpdateJob]]' );

		$this->pageCreator
			->getPage()
			->getTitle()
			->moveTo( $newTitle, false, 'test', true );

		// Execute the job directly
		// $this->assertJob( 'SMW\UpdateJob' );

		$this->assertTrue(
			$oldTitle->isRedirect()
		);

		$this->pageDeleter->deletePage(
			$oldTitle
		);
	}

	public function testSQLStoreRefreshDataTriggersUpdateJob() {

		$index = 1; //pass-by-reference

		$this->getStore()->refreshData( $index, 1, false, true )->rebuild( $index );
		$this->assertJob( 'smw.update' );
	}

	/**
	 * @dataProvider jobFactoryProvider
	 */
	public function testJobFactory( $jobName, $type ) {

		$job = Job::factory(
			$jobName,
			Title::newFromText( __METHOD__ . $jobName ),
			[]
		);

		$this->assertJob( $type, $job );
	}

	public function jobFactoryProvider() {

		$provider = [];

		$provider[] = [ 'SMW\UpdateJob', 'smw.update' ];
		$provider[] = [ 'SMW\UpdateJob', 'SMW\UpdateJob' ];
		$provider[] = [ 'SMWUpdateJob', 'SMW\UpdateJob' ];

		$provider[] = [ 'SMW\RefreshJob', 'smw.refresh' ];
		$provider[] = [ 'SMW\RefreshJob', 'SMW\RefreshJob' ];
		$provider[] = [ 'SMWRefreshJob', 'SMW\RefreshJob' ];

		return $provider;
	}

	public function titleProvider() {

		$provider = [];

		// #0 Simple property reference
		$provider[] = [ [
				'title' => Title::newFromText( __METHOD__ . '-foo' ),
				'edit'  => '{{#set:|DeferredJobFoo=DeferredJobBar}}'
			], [
				'title' => Title::newFromText( __METHOD__ . '-bar' ),
				'edit'  => '{{#set:|DeferredJobFoo=DeferredJobBar}}'
			]
		];

		// #1 Source page in-property reference
		$title = Title::newFromText( __METHOD__ . '-foo' );

		$provider[] = [ [
				'title' => $title,
				'edit'  => ''
			], [
				'title' => Title::newFromText( __METHOD__ . '-bar' ),
				'edit'  => '{{#set:|DeferredJobFoo=' . $title->getPrefixedText() . '}}'
			]
		];

		return $provider;
	}

	protected function assertJob( $type, Job &$job = null ) {

		if ( $job === null ) {
			$job = $this->jobQueueRunner->pop_type( $type );
		}

		if ( !$job ) {
			$this->markTestSkipped( "Required a {$type} JobQueue entry" );
		}

		$this->job = $job;

		$this->assertInstanceOf( 'Job', $job );
		$this->assertTrue( $job->run() );
	}

	/**
	 * Issue 617
	 */
	public function testNoInfiniteUpdateJobsForCircularRedirect() {

		$this->skipTestForMediaWikiVersionLowerThan( '1.20' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Foo-A' ) )
			->doEdit( '[[Foo-A::{{PAGENAME}}]] {{#ask: [[Foo-A::{{PAGENAME}}]] }}' )
			->doEdit( '#REDIRECT [[Foo-B]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Foo-B' ) )
			->doEdit( '#REDIRECT [[Foo-C]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Foo-C' ) )
			->doEdit( '#REDIRECT [[Foo-A]]' );

		$this->jobQueueRunner
			->setType( 'SMW\UpdateJob' )
			->run();

		foreach ( $this->jobQueueRunner->getStatus() as $status ) {
			$this->assertTrue( $status['status'] );
		}

		$this->assertTrue(
			Title::newFromText( 'Foo-A' )->isRedirect()
		);

		$this->deletePoolOfPages = [
			Title::newFromText( 'Foo-A' ),
			Title::newFromText( 'Foo-B' ),
			Title::newFromText( 'Foo-C' )
		];
	}

}
