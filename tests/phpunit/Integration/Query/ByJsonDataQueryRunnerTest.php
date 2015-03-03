<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\ApplicationFactory;
use Title;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ByJsonDataQueryRunnerTest extends MwDBaseUnitTestCase {

	/**
	 * Version to match supported Json format
	 *
	 * @var string
	 */
	const JSON_VERSION = '0.1';

	/**
	 * @var FileReader
	 */
	private $fileReader;

	/**
	 * @var QueryDefinitionTestCaseProcessor
	 */
	private $queryDefinitionTestCaseProcessor;

	/**
	 * Settings enabled for local change
	 *
	 * @var array
	 */
	private $settings = array();

	private $itemsForDeletion = array();
	private $deleteAfterState = true;
	private $pageCreator;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->fileReader = $utilityFactory->newJsonFileReader( null );
		$this->pageCreator = $utilityFactory->newPageCreator();

		$this->queryDefinitionTestCaseProcessor = new QueryDefinitionTestCaseProcessor(
			$this->getStore(),
			ApplicationFactory::getInstance()->newQueryParser(),
			$utilityFactory->newValidatorFactory()->newQueryResultValidator()
		);
	}

	protected function tearDown() {

		if ( $this->deleteAfterState ) {
			UtilityFactory::getInstance()->newPageDeleter()->doDeletePoolOfPages( $this->itemsForDeletion );
		}

		$this->restoreSettingsBeforeLocalChange();
		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider queryDefinitionFileProvider
	 */
	public function executeQueryDefinition( $file ) {

		$this->fileReader->setFile( $file );

		$dataToQueryDefinitionFileHandler = new DataToQueryDefinitionFileHandler( $this->fileReader );

		$this->verifyTestEnviroment( $dataToQueryDefinitionFileHandler );

		// smwgQMaxSize -> Query::applyRestrictions

		foreach ( array( 'smwgQMaxSize', 'smwStrictComparators', 'smwgNamespacesWithSemanticLinks' ) as $key ) {
			$this->changeSettingTo( $key, $dataToQueryDefinitionFileHandler->getSettingsFor( $key ) );
		}

		$this->createPagesFor( $dataToQueryDefinitionFileHandler->getPropertyDefinitions(), SMW_NS_PROPERTY );
		$this->createPagesFor( $dataToQueryDefinitionFileHandler->getSubjectDefinitions(), NS_MAIN );

		$this->queryDefinitionTestCaseProcessor->setDebugMode(
			$dataToQueryDefinitionFileHandler->getDebugMode()
		);

		foreach ( $dataToQueryDefinitionFileHandler->getQueryDefinitions() as $queryDefinition ) {
			$this->queryDefinitionTestCaseProcessor->processQueryDefinition( new QueryDefinitionInterpreter( $queryDefinition ) );
		}

		foreach ( $dataToQueryDefinitionFileHandler->getConceptDefinitions() as $conceptDefinition ) {
			$this->queryDefinitionTestCaseProcessor->processConceptDefinition( new QueryDefinitionInterpreter( $conceptDefinition ) );
		}
	}

	private function createPagesFor( array $pages, $defaultNamespace ) {

		foreach ( $pages as $page ) {

			if ( !isset( $page['name'] ) || !isset( $page['contents'] ) ) {
				continue;
			}

			$namespace = isset( $page['namespace'] ) ? constant( $page['namespace'] ) : $defaultNamespace;

			$this->pageCreator
				->createPage( Title::newFromText( $page['name'], $namespace ) )
				->doEdit( $page['contents'] );

			$this->itemsForDeletion[] = $this->pageCreator->getPage();
		}
	}

	public function queryDefinitionFileProvider() {

		$provider = array();

		$bulkFileProvider = UtilityFactory::getInstance()->newBulkFileProvider( __DIR__ );
		$bulkFileProvider->searchByFileExtension( 'json' );

		foreach ( $bulkFileProvider->getFiles() as $file ) {
			$provider[ basename( $file ) ] = array( $file );
		}

		return $provider;
	}

	private function verifyTestEnviroment( DataToQueryDefinitionFileHandler $dataToQueryDefinitionFileHandler ) {

		if ( $dataToQueryDefinitionFileHandler->isIncomplete() ) {
			$this->markTestIncomplete( $dataToQueryDefinitionFileHandler->getReasonForSkip() );
		}

		if ( $dataToQueryDefinitionFileHandler->requiredToSkipForVersion( self::JSON_VERSION ) ) {
			$this->markTestSkipped( $dataToQueryDefinitionFileHandler->getReasonForSkip() );
		}

		if ( $dataToQueryDefinitionFileHandler->requiredToSkipForConnector( $this->getDBConnection()->getType() ) ) {
			$this->markTestSkipped( $dataToQueryDefinitionFileHandler->getReasonForSkip() );
		}

		if ( $dataToQueryDefinitionFileHandler->requiredToSkipForConnector( $GLOBALS['smwgSparqlDatabaseConnector'] ) ) {
			$this->markTestSkipped( $dataToQueryDefinitionFileHandler->getReasonForSkip() );
		}
	}

	private function changeSettingTo( $key, $value ) {

		if ( $key === '' || $value === '' ) {
			return;
		}

		$this->settings[$key] = $GLOBALS[$key];
		$GLOBALS[$key] = $value;
		ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
	}

	private function restoreSettingsBeforeLocalChange() {
		foreach ( $this->settings as $key => $value ) {
			$GLOBALS[$key] = $value;
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}
	}

}
