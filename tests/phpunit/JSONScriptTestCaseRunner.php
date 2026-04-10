<?php

namespace SMW\Tests;

use Closure;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use SMW\Elastic\ElasticStore;
use SMW\Localizer\Localizer;
use SMW\NamespaceManager;
use SMW\SPARQLStore\SPARQLStore;
use SMW\Tests\Utils\File\JsonFileReader;
use SMW\Tests\Utils\JSONScript\JsonTestCaseContentHandler;
use SMW\Tests\Utils\JSONScript\JsonTestCaseFileHandler;
use SMW\Tests\Utils\UtilityFactory;

/**
 * The `JSONScriptTestCaseRunner` is a convenience provider for `Json` formatted
 * integration tests to allow writing tests quicker without the need to setup
 * or tear down specific data structures.
 *
 * The JSON format should make it also possible for novice user to understand
 * what sort of tests are run as the content is based on wikitext rather than
 * native PHP.
 *
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
abstract class JSONScriptTestCaseRunner extends SMWIntegrationTestCase {

	private JsonFileReader $fileReader;
	private JsonTestCaseContentHandler $jsonTestCaseContentHandler;
	private array $itemsMarkedForDeletion = [];
	private array $configValueCallback = [];
	protected bool $deletePagesOnTearDown = true;
	protected string $searchByFileExtension = 'json';
	protected string $connectorId = '';

	protected function setUp(): void {
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

		if ( $this->getStore() instanceof SPARQLStore ) {
			if ( isset( $GLOBALS['smwgSparqlDatabaseConnector'] ) ) {
				$connectorId = $GLOBALS['smwgSparqlDatabaseConnector'];
			} else {
				$connectorId = $GLOBALS['smwgSparqlRepositoryConnector'];
			}

			$this->connectorId = strtolower( $connectorId );
		} elseif ( $this->getStore() instanceof ElasticStore ) {
			$this->connectorId = 'elastic';
		} else {
			$this->connectorId = strtolower( $this->getDb()->getType() );
		}
	}

	protected function tearDown(): void {
		try {
			if ( $this->deletePagesOnTearDown ) {
				$this->testEnvironment->flushPages( $this->itemsMarkedForDeletion );
			}
		} finally {
			try {
				$this->testEnvironment->tearDown();
			} finally {
				parent::tearDown();
			}
		}
	}

	abstract protected function getTestCaseLocation(): string;

	abstract protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ): void;

	protected function getRequiredJsonTestCaseMinVersion(): string {
		return '0.1';
	}

	protected function getAllowedTestCaseFiles(): array {
		return [];
	}

	/**
	 * @since 3.0
	 */
	protected function getDependencyDefinitions(): array {
		return [];
	}

	/**
	 * Selected list of settings (internal or MediaWiki related) that are
	 * permissible for the time of the test run to be manipulated.
	 *
	 * For a configuration that requires special treatment (i.e. where a simple
	 * assignment isn't sufficient), a callback can be assigned to a settings
	 * key in order to sort out required manipulation (constants etc.).
	 */
	protected function getPermittedSettings(): array {
		// Ensure that the context is set for a selected language
		// and dependent objects are reset
		$this->registerConfigValueCallback( 'wgContLang', function ( $val ) {
			RequestContext::getMain()->setLanguage( $val );
			Localizer::clear();
			// #4682, Avoid any surprises when the `wgLanguageCode` is changed during a test
			NamespaceManager::clear();

			// Reset title-related services to prevent stale language objects. See #5951.
			$this->testEnvironment->resetMediaWikiService( 'TitleParser' );
			$this->testEnvironment->resetMediaWikiService( '_MediaWikiTitleCodec' );

			$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
			$lang = $languageFactory->getLanguage( $val );

			// https://github.com/wikimedia/mediawiki/commit/49ce67be93dfbb40d036703dad2278ea9843f1ad
			$this->testEnvironment->redefineMediaWikiService( 'ContentLanguage', static function () use ( $lang ) {
				return $lang;
			} );

			return $lang;
		} );

		$this->registerConfigValueCallback( 'wgLang', static function ( $val ) {
			RequestContext::getMain()->setLanguage( $val );
			Localizer::clear();
			NamespaceManager::clear();
			$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
			$lang = $languageFactory->getLanguage( $val );
			return $lang;
		} );

		return [];
	}

	protected function registerConfigValueCallback( string $key, Closure $callback ): void {
		$this->configValueCallback[$key] = $callback;
	}

	protected function getConfigValueCallback( string $key ): ?callable {
		return $this->configValueCallback[$key] ?? null;
	}

	/**
	 * Normally returns TRUE but can act on the list retrieved from
	 * JsonTestCaseScriptRunner::getAllowedTestCaseFiles (or hereof) to filter
	 * selected files and help fine tune a setup or debug a potential issue
	 * without having to run all test files at once.
	 */
	protected function canTestCaseFile( string $file ): bool {
		// Filter specific files on-the-fly
		$allowedTestCaseFiles = $this->getAllowedTestCaseFiles();

		if ( $allowedTestCaseFiles === [] ) {
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
	 * @dataProvider jsonFileProvider
	 */
	public function testCaseFile( string $file ): void {
		if ( !$this->canTestCaseFile( $file ) ) {
			$this->markTestSkipped( $file . ' excluded from the test run' );
		}

		$this->fileReader->setFile( $file );
		$this->runTestCaseFile( new JsonTestCaseFileHandler( $this->fileReader ) );
	}

	public function jsonFileProvider(): array {
		$provider = [];

		$bulkFileProvider = UtilityFactory::getInstance()->newBulkFileProvider(
			$this->getTestCaseLocation()
		);

		$bulkFileProvider->searchByFileExtension( $this->searchByFileExtension );

		foreach ( $bulkFileProvider->getFiles() as $file ) {
			$provider[basename( $file )] = [ $file ];
		}

		return $provider;
	}

	/**
	 * @since 2.2
	 */
	protected function changeGlobalSettingTo( string $key, $value ): void {
		$this->testEnvironment->addConfiguration( $key, $value );
	}

	/**
	 * @since 2.2
	 */
	protected function checkEnvironmentToSkipCurrentTest( JsonTestCaseFileHandler $jsonTestCaseFileHandler ): void {
		if ( $jsonTestCaseFileHandler->isIncomplete() ) {
			$this->markTestIncomplete( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

		if ( !$jsonTestCaseFileHandler->hasAllRequirements( $this->getDependencyDefinitions() ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

		if ( $jsonTestCaseFileHandler->requiredToSkipForJsonVersion( $this->getRequiredJsonTestCaseMinVersion() ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

		if ( $jsonTestCaseFileHandler->requiredToSkipForMwVersion( MW_VERSION ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

		if ( $jsonTestCaseFileHandler->requiredToSkipOnSiteLanguage( $GLOBALS['wgLanguageCode'] ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}

		if ( $jsonTestCaseFileHandler->requiredToSkipForConnector( $this->getDb()->getType() ) ) {
			$this->markTestSkipped( $jsonTestCaseFileHandler->getReasonForSkip() );
		}
	}

	/**
	 * @since 2.5
	 */
	protected function createPagesFrom( array $pages, int $defaultNamespace = NS_MAIN ): void {
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

}
