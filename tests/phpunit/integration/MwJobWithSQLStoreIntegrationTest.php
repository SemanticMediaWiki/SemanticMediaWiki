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

	/**
	 * @since 1.9.0.1
	 */
	public function testArticleDeleteAssociativeEntitiesRefreshAsDeferredJob() {

		$store   = $this->getStore();
		$context = new ExtensionContext();

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $store );
		$context->getSettings()->set( 'smwgDeleteSubjectAsDeferredJob', true );
		$context->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', true );
		$context->getSettings()->set( 'smwgEnableUpdateJobs', true );

		$hasParameters = array( 'withAssociates', 'asDeferredJob', 'semanticData' );

		$this->runExtensionSetup( $context );

		$title = Title::newFromText( __METHOD__ );
		$dataItem = DIWikiPage::newFromTitle( $title );

		$this->assertSemanticDataIsEmpty( $store->getSemanticData( $dataItem ) );

		$this->createPage( $title, '{{#set:|DeferredJobFoo=DeferredJobBar}}' );
		$this->assertSemanticDataIsNotEmpty( $store->getSemanticData( $dataItem ) );

		$this->deletePage( $title );
		$this->assertSemanticDataIsEmpty( $store->getSemanticData( $dataItem ) );

		$this->assertJobRun( 'SMW\DeleteSubjectJob', null, $hasParameters );
		$this->assertJobRun( 'SMW\UpdateJob' );

	}

	/**
	 * @dataProvider jobFactoryProvider
	 *
	 * @since 1.9.0.2
	 */
	public function testJobFactory( $jobName, $type ) {

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgEnableUpdateJobs', true );

		$this->runExtensionSetup( $context );

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

	protected function assertJobRun( $type, Job $job = null, $hasParameters = array() ) {

		if ( $job === null ) {
			$job = Job::pop_type( $type );
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
