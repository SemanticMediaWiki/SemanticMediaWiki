<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Message;
use Title;

/**
 * The JsonTestCaseScriptRunner is a convenience provider for `Json` formatted
 * integration tests to allow writing tests quicker without the need to setup
 * or tear down specific data structures.
 *
 * The JSON format should make it also possible for novice user to understand
 * what sort of tests are run as the content is based on wikitext rather than
 * native PHP.
 *
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
abstract class JsonTestCaseScriptRunner extends MwDBaseUnitTestCase {

	/**
	 * @var FileReader
	 */
	private $fileReader;

	/**
	 * @var JsonTestCaseFileHandler
	 */
	private $jsonTestCaseFileHandler;

	/**
	 * @var JsonTestCaseContentHandler
	 */
	private $jsonTestCaseContentHandler;

	/**
	 * @var array
	 */
	private $itemsMarkedForDeletion = array();

	/**
	 * @var array
	 */
	private $configValueCallback = array();

	/**
	 * @var boolean
	 */
	protected $deletePagesOnTearDown = true;

	/**
	 * @var string
	 */
	protected $searchByFileExtension = 'json';

	/**
	 * @var string
	 */
	protected $connectorId = '';

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();
		$utilityFactory->newMwHooksHandler()->deregisterListedHooks();
		$utilityFactory->newMwHooksHandler()->invokeHooksFromRegistry();

		$this->fileReader = $utilityFactory->newJsonFileReader();

		$this->jsonTestCaseContentHandler = new JsonTestCaseContentHandler(
			$utilityFactory->newPageCreator(),
			$utilityFactory->newPageDeleter(),
			$utilityFactory->newLocalFileUpload()
		);

		if ( $this->getStore() instanceof \SMWSparqlStore ) {
			$this->connectorId = strtolower( $GLOBALS['smwgSparqlDatabaseConnector'] );
		} else {
			$this->connectorId = strtolower( $this->getDBConnection()->getType() );
		}
	}

	protected function tearDown() {

		if ( $this->deletePagesOnTearDown ) {
			$this->testEnvironment->flushPages( $this->itemsMarkedForDeletion );
		}

		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @return string
	 */
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
	 * @return array
	 */
	protected function getAllowedTestCaseFiles() {
		return array();
	}

	/**
	 * Selected list of settings (internal or MediaWiki related) that are
	 * permissible for the time of the test run to be manipulated.
	 *
	 * For a configuration that requires special treatment (i.e. where a simple
	 * assignment isn't sufficient), a callback can be assigned to a settings
	 * key in order to sort out required manipulation (constants etc.).
	 *
	 * @return array
	 */
	protected function getPermittedSettings() {

		// Ensure that the context is set for a select language
		// and dependent objects are reset
		$langCallback = function( $val ) {
			\RequestContext::getMain()->setLanguage( $val );
			\SMW\Localizer::getInstance()->clear();
			return \Language::factory( $val ); };

		$this->registerConfigValueCallback( 'wgContLang', $langCallback );
		$this->registerConfigValueCallback( 'wgLang', $langCallback );

		return array();
	}

	/**
	 * @param string $key
	 * @param Closure $callback
	 */
	protected function registerConfigValueCallback( $key, \Closure $callback ) {
		$this->configValueCallback[$key] = $callback;
	}

	/**
	 * @return callable|null
	 */
	protected function getConfigValueCallback( $key ) {
		return isset( $this->configValueCallback[$key] ) ? $this->configValueCallback[$key] : null;
	}

	/**
	 * Normally returns TRUE but can act on the list retrieved from
	 * JsonTestCaseScriptRunner::getAllowedTestCaseFiles (or hereof) to filter
	 * selected files and help fine tune a setup or debug a potential issue
	 * without having to run all test files at once.
	 *
	 * @param string $file
	 *
	 * @return boolean
	 */
	protected function canExecuteTestCasesFor( $file ) {

		// Filter specific files on-the-fly
		$allowedTestCaseFiles = $this->getAllowedTestCaseFiles();

		if ( $allowedTestCaseFiles === array() ) {
			return true;
		}

		// Doesn't require the exact name
		foreach ( $allowedTestCaseFiles as $fileName ) {
			if ( strpos( $file, $fileName ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @test
	 * @dataProvider jsonFileProvider
	 */
	public function executeTestCases( $file ) {

		if ( !$this->canExecuteTestCasesFor( $file ) ) {
			$this->markTestSkipped( $file . ' excluded from the test run' );
		}

		$this->fileReader->setFile( $file );
		$this->runTestCaseFile( new JsonTestCaseFileHandler( $this->fileReader ) );
	}

	/**
	 * @return array
	 */
	public function jsonFileProvider() {

		$provider = array();

		$bulkFileProvider = UtilityFactory::getInstance()->newBulkFileProvider(
			$this->getTestCaseLocation()
		);

		$bulkFileProvider->searchByFileExtension( $this->searchByFileExtension );

		foreach ( $bulkFileProvider->getFiles() as $file ) {
			$provider[basename( $file )] = array( $file );
		}

		return $provider;
	}

	/**
	 * @since 2.2
	 *
	 * @param mixed $key
	 * @param mixed $value
	 */
	protected function changeGlobalSettingTo( $key, $value ) {
		$this->testEnvironment->addConfiguration( $key, $value );
	}

	/**
	 * @since 2.2
	 *
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	protected function checkEnvironmentToSkipCurrentTest( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		if ( $jsonTestCaseFileHandler->isIncomplete() ) {
			$this->markTestIncomplete( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

		if ( $jsonTestCaseFileHandler->requiredToSkipForJsonVersion( $this->getRequiredJsonTestCaseMinVersion() ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

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

	/**
	 * @since 2.5
	 *
	 * @param array $pages
	 * @param integer $defaultNamespace
	 */
	protected function createPagesFrom( array $pages, $defaultNamespace = NS_MAIN ) {

		$this->jsonTestCaseContentHandler->skipOn(
			$this->connectorId
		);

		$this->jsonTestCaseContentHandler->setTestCaseLocation(
			$this->getTestCaseLocation()
		);

		$this->jsonTestCaseContentHandler->createPagesFrom(
			$pages,
			$defaultNamespace
		);

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->itemsMarkedForDeletion = $this->jsonTestCaseContentHandler->getPages();
	}

	/**
	 * @deprecated 2.5
	 */
	protected function createPagesFor( array $pages, $defaultNamespace ) {
		$this->createPagesFrom( $pages, $defaultNamespace );
	}

}
