<?php

namespace SMW\Tests;

use SMW\Tests\Utils\UtilityFactory;
use SMW\ApplicationFactory;
use Title;

/**
 * The JsonTestCase provider is a convenience provider for `Json` formatted
 * integration tests to allow writing tests quicker without the need to setup
 * or tear down specific data structures.
 *
 * The json format should make it also possible for novice user to understand
 * what sort of tests are run as the content is based on wikitext rather than
 * native PHP.
 *
 * Json files are read from a specified directory and invoked individually which
 * then will be executed by a TestCaseRunner.
 *
 * - ByJsonQueryTestCaseRunnerTest
 * - ByJsonRdfTestCaseRunnerTest
 *
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
abstract class ByJsonTestCaseProvider extends MwDBaseUnitTestCase {

	/**
	 * @var FileReader
	 */
	private $fileReader;

	/**
	 * @var PageCreator
	 */
	private $pageCreator;

	/**
	 * @var PageDeleter
	 */
	private $pageDeleter;

	/**
	 * @var JsonTestCaseFileHandler
	 */
	private $jsonTestCaseFileHandler;

	/**
	 * @var array
	 */
	private $settings = array();

	/**
	 * @var array
	 */
	private $itemsMarkedForDeletion = array();

	/**
	 * @var boolean
	 */
	private $deleteAfterState = true;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->fileReader = $utilityFactory->newJsonFileReader( null );
		$this->pageCreator = $utilityFactory->newPageCreator();
		$this->pageDeleter = $utilityFactory->newPageDeleter();
	}

	protected function tearDown() {

		if ( $this->deleteAfterState ) {
			UtilityFactory::getInstance()->newPageDeleter()->doDeletePoolOfPages( $this->itemsMarkedForDeletion );
		}

		$this->restoreSettingsBeforeLocalChange();
		parent::tearDown();
	}

	abstract protected function getTestCaseLocation();

	/**
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	abstract protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler );

	/**
	 * @return string
	 */
	protected function getRequiredJsonTestCaseMinVersion() {
		return '0.1';
	}

	/**
	 * @param string $file
	 * @return boolean
	 */
	protected function canExecuteTestCasesFor( $file ) {
		return true;
	}

	/**
	 * @test
	 * @dataProvider jsonFileProvider
	 */
	public function executeTestCasesFor( $file ) {

		if ( !$this->canExecuteTestCasesFor( $file ) ) {
			$this->markTestSkipped( $file . ' excluded from test run' );
		}

		$this->fileReader->setFile( $file );
		$this->runTestCaseFile( new JsonTestCaseFileHandler( $this->fileReader ) );
	}

	protected function createPagesFor( array $pages, $defaultNamespace ) {

		foreach ( $pages as $page ) {

			if ( !isset( $page['name'] ) || !isset( $page['contents'] ) ) {
				continue;
			}

			$namespace = isset( $page['namespace'] ) ? constant( $page['namespace'] ) : $defaultNamespace;

			$title = Title::newFromText(
				$page['name'],
				$namespace
			);

			$this->pageCreator
				->createPage( $title )
				->doEdit( $page['contents'] );

			$this->itemsMarkedForDeletion[] = $this->pageCreator->getPage();

			if ( isset( $page['move-to'] ) ) {

				$target = Title::newFromText(
					$page['move-to']['target'],
					$namespace
				);

				$this->pageCreator->doMoveTo(
					$target,
					$page['move-to']['is-redirect']
				);

				$this->itemsMarkedForDeletion[] = $target;
			}

			if ( isset( $page['do-delete'] ) && $page['do-delete'] ) {
				$this->pageDeleter->deletePage( $title );
			}
		}
	}

	public function jsonFileProvider() {

		$provider = array();

		$bulkFileProvider = UtilityFactory::getInstance()->newBulkFileProvider( $this->getTestCaseLocation() );
		$bulkFileProvider->searchByFileExtension( 'json' );

		foreach ( $bulkFileProvider->getFiles() as $file ) {
			$provider[basename( $file )] = array( $file );
		}

		return $provider;
	}

	protected function checkEnvironmentToSkipCurrentTest( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		if ( $jsonTestCaseFileHandler->isIncomplete() ) {
			$this->markTestIncomplete( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

	//	if ( $jsonTestCaseFileHandler->requiredToSkipForJsonVersion( $this->getRequiredJsonTestCaseMinVersion() ) ) {
		//	$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
	//	}

		if ( $jsonTestCaseFileHandler->requiredToSkipForMwVersion( $GLOBALS['wgVersion'] ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

		if ( $jsonTestCaseFileHandler->requiredToSkipForConnector( $this->getDBConnection()->getType() ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

		if ( $this->getStore() instanceof \SMWSparqlStore && $jsonTestCaseFileHandler->requiredToSkipForConnector( $GLOBALS['smwgSparqlDatabaseConnector'] ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}
	}

	protected function changeGlobalSettingTo( $key, $value ) {

		if ( $key === '' || $value === '' ) {
			return;
		}

		$this->settings[$key] = $GLOBALS[$key];
		$GLOBALS[$key] = $value;
		ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
	}

	protected function restoreSettingsBeforeLocalChange() {
		foreach ( $this->settings as $key => $value ) {
			$GLOBALS[$key] = $value;
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}
	}

}
