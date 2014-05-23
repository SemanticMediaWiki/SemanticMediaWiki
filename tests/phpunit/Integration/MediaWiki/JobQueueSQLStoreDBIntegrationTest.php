<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\PageCreator;
use SMW\Tests\Util\PageDeleter;
use SMW\Tests\Util\JobQueueRunner;

use SMW\Tests\MwDBSQLStoreIntegrationTestCase;

use SMW\ExtensionContext;
use SMW\SemanticData;
use SMW\StoreFactory;
use SMW\DIWikiPage;

use WikiPage;
use Title;
use Job;

/**
 * @ingroup Test
 *
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
class JobQueueSQLStoreDBIntegrationTest extends MwDBSQLStoreIntegrationTestCase {

	protected $job = null;

	protected function setUp() {
		parent::setUp();

		$context = new ExtensionContext();

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $this->getStore() );
		$context->getSettings()->set( 'smwgDeleteSubjectAsDeferredJob', true );
		$context->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', true );
		$context->getSettings()->set( 'smwgEnableUpdateJobs', true );

		$this->runExtensionSetup( $context );

		$jobQueueRunner = new JobQueueRunner( null, $this->getDBConnectionProvider() );
		$jobQueueRunner->deleteAllJobs();
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testPageDeleteTriggersDeleteSubjectJob( $source, $associate ) {

		$semanticDataValidator = new SemanticDataValidator();

		$subject = DIWikiPage::newFromTitle( $source['title'] );

		$semanticDataValidator->assertThatSemanticDataIsEmpty(
			$this->getStore()->getSemanticData( $subject )
		);

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $source['title'] )
			->doEdit( $source['edit'] );

		$pageCreator
			->createPage( $associate['title'] )
			->doEdit( $associate['edit'] );

		$semanticDataValidator->assertThatSemanticDataIsNotEmpty(
			$this->getStore()->getSemanticData( $subject )
		);

		$this->deletePage( $source['title'] );

		$semanticDataValidator->assertThatSemanticDataIsEmpty(
			$this->getStore()->getSemanticData( $subject )
		);

		$this->assertJob( 'SMW\DeleteSubjectJob' );

		foreach ( array( 'withAssociates', 'asDeferredJob', 'semanticData' ) as $parameter ) {
			$this->assertTrue( $this->job->hasParameter( $parameter ) );
		}

		$this->deletePage( $associate['title'] );
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

		$this->deletePage( $oldTitle );
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
