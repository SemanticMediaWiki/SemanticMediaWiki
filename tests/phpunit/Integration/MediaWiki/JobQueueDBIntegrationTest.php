<?php

namespace SMW\Tests\Integration\MediaWiki;

use Job;
use SMW\ApplicationFactory;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.0.1
 *
 * @author mwjames
 */
class JobQueueDBIntegrationTest extends MwDBaseUnitTestCase {

	private $job = null;
	private $applicationFactory;

	private $deletePoolOfPages = array();
	private $runnerFactory;

	private $mwHooksHandler;
	private $semanticDataValidator;

	private $pageDeleter;
	private $pageCreator;

	private $jobQueueRunner;
	private $jobQueueLookup;

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

		$settings = array(
			'smwgEnableUpdateJobs' => true,
			'smwgDeleteSubjectAsDeferredJob' => true,
			'smwgDeleteSubjectWithAssociatesRefresh' => true
		);

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		$this->jobQueueLookup = $this->applicationFactory
			->newMwCollaboratorFactory()
			->newJobQueueLookup( $this->getStore()->getConnection( 'mw.db' ) );

		$this->jobQueueRunner = $utilityFactory->newRunnerFactory()->newJobQueueRunner();

		$this->jobQueueRunner
			->setDBConnectionProvider( $this->getDBConnectionProvider() )
			->deleteAllJobs();
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

		$this->getStore()->refreshData( $index, 1, false, true )->dispatchRebuildFor( $index );
		$this->assertJob( 'SMW\UpdateJob' );
	}

	/**
	 * @dataProvider jobFactoryProvider
	 */
	public function testJobFactory( $jobName, $type ) {

		$job = Job::factory(
			$jobName,
			Title::newFromText( __METHOD__ . $jobName ),
			array()
		);

		$this->assertJob( $type, $job );
	}

	public function jobFactoryProvider() {

		$provider = array();

		$provider[] = array( 'SMW\UpdateJob', 'SMW\UpdateJob' );
		$provider[] = array( 'SMWUpdateJob', 'SMW\UpdateJob' );

		$provider[] = array( 'SMW\RefreshJob', 'SMW\RefreshJob' );
		$provider[] = array( 'SMWRefreshJob', 'SMW\RefreshJob' );

		return $provider;
	}

	public function titleProvider() {

		$provider = array();

		// #0 Simple property reference
		$provider[] = array( array(
				'title' => Title::newFromText( __METHOD__ . '-foo' ),
				'edit'  => '{{#set:|DeferredJobFoo=DeferredJobBar}}'
			), array(
				'title' => Title::newFromText( __METHOD__ . '-bar' ),
				'edit'  => '{{#set:|DeferredJobFoo=DeferredJobBar}}'
			)
		);

		// #1 Source page in-property reference
		$title = Title::newFromText( __METHOD__ . '-foo' );

		$provider[] = array( array(
				'title' => $title,
				'edit'  => ''
			), array(
				'title' => Title::newFromText( __METHOD__ . '-bar' ),
				'edit'  => '{{#set:|DeferredJobFoo=' . $title->getPrefixedText() . '}}'
			)
		);

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

		$this->deletePoolOfPages = array(
			Title::newFromText( 'Foo-A' ),
			Title::newFromText( 'Foo-B' ),
			Title::newFromText( 'Foo-C' )
		);
	}

	public function testPropertyTypeChangeToCreateUpdateJob() {

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

		// Secondary dispatch process
		$this->assertGreaterThan(
			0,
			$this->jobQueueLookup->estimateJobCountFor( 'SMW\UpdateDispatcherJob' )
		);

		$this->jobQueueRunner
			->setType( 'SMW\UpdateDispatcherJob' )
			->run();

		$this->assertGreaterThan(
			0,
			$this->jobQueueLookup->estimateJobCountFor( 'SMW\UpdateJob' )
		);

		$this->jobQueueRunner
			->setType( 'SMW\UpdateJob' )
			->run();

		foreach ( $this->jobQueueRunner->getStatus() as $status ) {
			$this->assertTrue( $status['status'] );
		}

		$this->deletePoolOfPages = array(
			$propertyPage,
			Title::newFromText( 'Foo' )
		);
	}

}
