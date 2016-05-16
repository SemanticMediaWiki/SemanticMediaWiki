<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;
use SMW\Tests\Utils\UtilityFactory;
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
	protected $deleteAfterState = true;

	/**
	 * @var string
	 */
	protected $connectorId = '';

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();
		$utilityFactory->newMwHooksHandler()->deregisterListedHooks();
		$utilityFactory->newMwHooksHandler()->invokeHooksFromRegistry();

		$this->fileReader = $utilityFactory->newJsonFileReader( null );
		$this->pageCreator = $utilityFactory->newPageCreator();
		$this->pageDeleter = $utilityFactory->newPageDeleter();

		if ( $this->getStore() instanceof \SMWSparqlStore ) {
			$this->connectorId = strtolower( $GLOBALS['smwgSparqlDatabaseConnector'] );
		} else {
			$this->connectorId = strtolower( $this->getDBConnection()->getType() );
		}
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

			$skipOn = isset( $page['skip-on'] ) ? $page['skip-on'] : array();

			if ( in_array( $this->connectorId, array_keys( $skipOn ) ) ) {
				continue;
			}

			if ( !isset( $page['name'] ) || !isset( $page['contents'] ) ) {
				continue;
			}

			$namespace = isset( $page['namespace'] ) ? constant( $page['namespace'] ) : $defaultNamespace;
			$pageContentLanguage = isset( $page['contentlanguage'] ) ? $page['contentlanguage'] : '';

			$title = Title::newFromText(
				$page['name'],
				$namespace
			);

			if ( is_array( $page['contents'] ) && isset( $page['contents']['import-from'] ) ) {
				$contents = file_get_contents( $this->getTestCaseLocation() . $page['contents']['import-from'] );
			} else {
				$contents = $page['contents'];
			}

			$this->pageCreator->createPage( $title, $contents, $pageContentLanguage );

			$this->itemsMarkedForDeletion[] = $this->pageCreator->getPage();

			if ( isset( $page['move-to'] ) ) {
				$this->doMove( $page, $namespace );
			}

			if ( isset( $page['do-purge'] ) ) {
				$this->pageCreator->getPage()->doPurge();
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

	private function doMove( $page, $namespace ) {
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

}
