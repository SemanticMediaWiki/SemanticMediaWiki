<?php

namespace SMW\Tests\Integration\MediaWiki\Jobs;

use Job;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 1.9.1
 *
 * @author mwjames
 */
class UpdateJobRoundtripTest extends SMWIntegrationTestCase {

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

	protected function setUp(): void {
		$utilityFactory = UtilityFactory::getInstance();
		$this->applicationFactory = ApplicationFactory::getInstance();

		$GLOBALS['smwgEnableUpdateJobs'] = true;
		$settings = [
			'smwgEnableUpdateJobs' => true
		];

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		parent::runJobs( [ 'minJobs' => 0 ], [ 'complete' => true ] );

		$this->jobQueue = $this->applicationFactory->getJobQueue();
		$this->jobQueueRunner = $utilityFactory->newRunnerFactory()->newJobQueueRunner();
	}

	protected function tearDown(): void {
		$this->applicationFactory->clear();
		parent::tearDown();
	}

	public function testPageMoveTriggersUpdateJobWithImmediateExecution() {
		// configured to run immediately, so after it was run, the number of exprected jobs in queue will be 0
		parent::runJobs( [ 'minJobs' => 0, 'complete' => false ] );

		$newTitle = Title::newFromText( __METHOD__ . '-new' );

		$wikiPage = parent::getNonexistingTestPage( __METHOD__ . '-old' );
		parent::editPage( $wikiPage, '[[Has jobqueue test::UpdateJob]]' );
		$title = $wikiPage->getTitle();

		// ---- taken from mediawiki/tests/phpunit/includes/page/MovePageTest.php
		$createRedirect = true;
		$pageId = $title->getArticleID();
		$status = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage( $title, $newTitle )
			->move( $this->getTestUser()->getUser(), 'move reason', $createRedirect );
		$this->assertStatusOK( $status );
		// ====

		// expected 0 jobs in jobQueue because the job is run instantly with immediate execution
		parent::runJobs( [ 'numJobs' => 0 ], [ 'type' => 'smw.update' ] );
	}

	public function testSQLStoreRefreshDataTriggersUpdateJob() {
		$index = 1; // pass-by-reference

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

	protected function assertJob( $type, ?Job &$job = null ) {
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

	public function testNoInfiniteUpdateJobsForCircularRedirect() {
		$pageA = parent::getNonexistingTestPage( 'Foo-A' );
		$pageB = parent::getNonexistingTestPage( 'Foo-B' );
		$pageC = parent::getNonexistingTestPage( 'Foo-C' );

		parent::editPage( $pageA, '[[Foo-A::{{PAGENAME}}]] {{#ask: [[Foo-A::{{PAGENAME}}]] }}' );
		parent::editPage( $pageA, '#REDIRECT [[Foo-B]]' );
		$titleA = $pageA->getTitle();

		parent::editPage( $pageB, '#REDIRECT [[Foo-C]]' );
		$titleB = $pageB->getTitle();

		parent::editPage( $pageC, '#REDIRECT [[Foo-A]]' );
		$titleC = $pageC->getTitle();

		// expected 0 jobs in jobQueue because the job is run instantly with immediate execution
		parent::runJobs( [ 'minJobs' => 0 ], [ 'type' => 'smw.update' ] );

		foreach ( $this->jobQueueRunner->getStatus() as $status ) {
			$this->assertTrue( $status['status'] );
		}

		$this->assertTrue(
			Title::newFromText( 'Foo-A' )->isRedirect()
		);
	}
}
