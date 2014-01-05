<?php

namespace SMW\Test;

use SMW\ExtensionContext;
use SMW\SemanticData;
use SMW\StoreFactory;
use SMW\DIWikiPage;

use WikiPage;
use Title;

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
	public function testArticleDeleteAsDeferredJob() {

		$store   = $this->getStore();
		$context = new ExtensionContext();

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $store );
		$context->getSettings()->set( 'smwgDeleteSubjectAsDeferredJob', true );
		$context->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', true );
		$context->getSettings()->set( 'smwgEnableUpdateJobs', true );

		$this->runExtensionSetup( $context );

		$title = Title::newFromText( __METHOD__ );
		$dataItem = DIWikiPage::newFromTitle( $title );

		$this->assertSemanticDataIsEmpty( $store->getSemanticData( $dataItem ) );

		$this->createPage( $title );
		$this->assertSemanticDataIsNotEmpty( $store->getSemanticData( $dataItem ) );

		$this->deletePage( $title );
		$this->assertSemanticDataIsNotEmpty( $store->getSemanticData( $dataItem ) );

		$this->assertJobRun( 'SMW\DeleteSubjectJob' );
		$this->assertSemanticDataIsEmpty( $store->getSemanticData( $dataItem ) );

	}

	protected function assertJobRun( $type ) {
		$job = \Job::pop_type( $type );

		$this->assertTrue(
			$job->run(),
			'Asserts a successful Job execution'
		);
	}

}
