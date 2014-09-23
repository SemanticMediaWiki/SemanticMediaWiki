<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\UtilityFactory;
use SMW\Tests\Util\PageCreator;

use SMW\Tests\MwDBaseUnitTestCase;

use SMW\Application;
use SMW\SemanticData;
use SMW\StoreFactory;
use SMW\DIWikiPage;

use WikiPage;
use Title;
use Job;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.0.1
 *
 * @author mwjames
 */
class JobQueueSQLStoreDBIntegrationTest extends MwDBaseUnitTestCase {

	private $job = null;
	private $application;

	private $runnerFactory;
	private $mwHooksHandler;
	private $semanticDataValidator;
	private $pageDeleter;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$this->application = Application::getInstance();

		// FIXME Because of SQLStore::Writer::changeTitle
		$GLOBALS['smwgEnableUpdateJobs'] = true;

		$settings = array(
			'smwgEnableUpdateJobs' => true,
			'smwgDeleteSubjectAsDeferredJob' => true,
			'smwgDeleteSubjectWithAssociatesRefresh' => true
		);

		foreach ( $settings as $key => $value ) {
			$this->application->getSettings()->set( $key, $value );
		}

		$this->pageDeleter = UtilityFactory::getInstance()->newPageDeleter();

		$jobQueueRunner = UtilityFactory::getInstance()->newRunnerFactory()->newJobQueueRunner();
		$jobQueueRunner
			->setDBConnectionProvider( $this->getDBConnectionProvider() )
			->deleteAllJobs();
	}

	protected function tearDown() {
		$this->application->clear();
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testPageDeleteTriggersDeleteSubjectJob( $source, $associate ) {

		$subject = DIWikiPage::newFromTitle( $source['title'] );

		$this->semanticDataValidator->assertThatSemanticDataIsEmpty(
			$this->getStore()->getSemanticData( $subject )
		);

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $source['title'] )
			->doEdit( $source['edit'] );

		$pageCreator
			->createPage( $associate['title'] )
			->doEdit( $associate['edit'] );

		$this->semanticDataValidator->assertThatSemanticDataIsNotEmpty(
			$this->getStore()->getSemanticData( $subject )
		);

		$this->pageDeleter->deletePage( $source['title'] );

		$this->semanticDataValidator->assertThatSemanticDataIsEmpty(
			$this->getStore()->getSemanticData( $subject )
		);

		$this->assertJob( 'SMW\DeleteSubjectJob' );

		foreach ( array( 'withAssociates', 'asDeferredJob', 'semanticData' ) as $parameter ) {
			$this->assertTrue( $this->job->hasParameter( $parameter ) );
		}

		$this->pageDeleter->deletePage( $associate['title'] );
	}

	public function testPageMoveTriggersUpdateJob() {

		$oldTitle = Title::newFromText( __METHOD__ . '-old' );
		$newTitle = Title::newFromText( __METHOD__ . '-new' );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $oldTitle )
			->doEdit( '[[Has jobqueue test::UpdateJob]]' );

		$pageCreator
			->getPage()
			->getTitle()
			->moveTo( $newTitle, false, 'test', true );

		$this->assertJob( 'SMW\UpdateJob' );

		$this->assertContains(
			$this->job->getTitle()->getPrefixedText(),
			array( $oldTitle->getPrefixedText(), $newTitle->getPrefixedText() )
		);

		$this->pageDeleter->deletePage( $oldTitle );
	}

	public function testSQLStoreRefreshDataTriggersUpdateJob() {

		$index = 1; //pass-by-reference

		$this->getStore()->refreshData( $index, 1, false, true );
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
			$job = Job::pop_type( $type );
		}

		if ( !$job ) {
			$this->markTestSkipped( "Required a {$type} JobQueue entry" );
		}

		$this->job = $job;

		$this->assertInstanceOf( 'Job', $job );
		$this->assertTrue( $job->run() );
	}

}
