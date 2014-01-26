<?php

namespace SMW\Test;

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
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.1
 *
 * @author mwjames
 */
class MwJobWithSQLStoreIntegrationTest extends MwIntegrationTestCase {

	protected function setUp() {
		parent::setUp();

		$context = new ExtensionContext();

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $this->getStore() );
		$context->getSettings()->set( 'smwgDeleteSubjectAsDeferredJob', true );
		$context->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', true );
		$context->getSettings()->set( 'smwgEnableUpdateJobs', true );

		$this->runExtensionSetup( $context );
	}

	/**
	 * @dataProvider titleProvider
	 *
	 * @since 1.9.0.1
	 */
	public function testArticleDeleteAssociativeEntitiesRefreshAsDeferredJob( $source, $associate ) {

		$semanticDataValidator = new SemanticDataValidator;

		$subject = DIWikiPage::newFromTitle( $source['title'] );

		$semanticDataValidator->assertThatSemanticDataIsEmpty( $this->getStore()->getSemanticData( $subject ) );

		$this->createPage( $source['title'], $source['edit'] );
		$this->createPage( $associate['title'], $associate['edit']  );

		$semanticDataValidator->assertThatSemanticDataIsNotEmpty( $this->getStore()->getSemanticData( $subject ) );

		$this->deletePage( $source['title'] );

		$semanticDataValidator->assertThatSemanticDataIsEmpty( $this->getStore()->getSemanticData( $subject ) );
		$this->assertJobRun( 'SMW\DeleteSubjectJob', null, array( 'withAssociates', 'asDeferredJob', 'semanticData' ) );
		$this->assertJobRun( 'SMW\UpdateJob' );

		$this->deletePage( $associate['title'] );

	}

	/**
	 * @dataProvider jobFactoryProvider
	 *
	 * @since 1.9.0.2
	 */
	public function testJobFactory( $jobName, $type ) {
		$job = Job::factory( $jobName, Title::newFromText( __METHOD__ . $jobName ), array() );
		$this->assertJobRun( $type, $job );
	}

	/**
	 * @return array
	 */
	public function jobFactoryProvider() {

		$provider = array();

		$provider[] = array( 'SMW\UpdateJob', 'SMW\UpdateJob' );
		$provider[] = array( 'SMWUpdateJob', 'SMW\UpdateJob' );

		$provider[] = array( 'SMW\RefreshJob', 'SMW\RefreshJob' );
		$provider[] = array( 'SMWRefreshJob', 'SMW\RefreshJob' );

		return $provider;
	}

	/**
	 * @return array
	 */
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

	protected function assertJobRun( $type, Job $job = null, $hasParameters = array() ) {

		if ( $job === null ) {
			$job = Job::pop_type( $type );
		}

		if ( !$job ) {
			$this->markTestSkipped( "Test only applicable for when a {$type} JobQueue entry is available" );
		}

		$this->assertInstanceOf( 'Job', $job );

		$this->assertTrue(
			$job->run(),
			'Asserts a successful Job execution'
		);

		foreach ( $hasParameters as $hasParameter ) {
			$this->assertTrue( $job->hasParameter( $hasParameter ) );
		}

	}

}
