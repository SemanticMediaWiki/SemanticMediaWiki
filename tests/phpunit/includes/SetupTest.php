<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;
use SMW\Setup;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Setup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SetupTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $defaultConfig;

	protected function setUp() {
		parent::setUp();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( [] ) );

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->defaultConfig = [
			'smwgMainCacheType' => CACHE_NONE,
			'smwgNamespacesWithSemanticLinks' => [],
			'smwgEnableUpdateJobs' => false,
			'wgNamespacesWithSubpages' => [],
			'wgExtensionAssetsPath'    => false,
			'smwgResourceLoaderDefFiles' => $GLOBALS['smwgResourceLoaderDefFiles'],
			'wgResourceModules' => [],
			'wgScriptPath'      => '/Foo',
			'wgServer'          => 'http://example.org',
			//'wgVersion'         => '1.21',
			'wgLanguageCode'    => 'en',
			'wgLang'            => $language,
			'IP'                => 'Foo',
			'smwgSemanticsEnabled' => true,
			'smwgConfigFileDir' => ''
		];

		$this->testEnvironment = new TestEnvironment( $this->defaultConfig );
		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Setup::class,
			new Setup()
		);
	}

	public function testResourceModules() {

		$config = $this->defaultConfig;

		$instance = new Setup();
		$instance->init( $config, '' );

		$this->assertNotEmpty(
			$config['wgResourceModules']
		);
	}

	/**
	 * @dataProvider apiModulesDataProvider
	 */
	public function testGetAPIModules( $name ) {

		$vars = Setup::getAPIModules();

		$this->assertArrayHasKey(
			$name,
			$vars
		);
	}

	/**
	 * @dataProvider jobClassesDataProvider
	 */
	public function testRegisterJobClasses( $jobEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgJobClasses', $jobEntry, $setup );
	}

	/**
	 * @dataProvider specialPageDataProvider
	 */
	public function testInitSpecialPageList( $name ) {

		$vars = [];

		Setup::initSpecialPageList( $vars );

		$this->assertArrayHasKey(
			$name,
			$vars
		);
	}

	public function testRegisterDefaultRightsUserGroupPermissions() {

		$config = $this->defaultConfig;

		$instance = new Setup();
		$instance->init( $config, 'Foo' );

		$this->assertNotEmpty(
			$config['wgAvailableRights']
		);

		$this->assertTrue(
			$config['wgGroupPermissions']['sysop']['smw-admin']
		);

		$this->assertTrue(
			$config['wgGroupPermissions']['smwcurator']['smw-patternedit']
		);

		$this->assertTrue(
			$config['wgGroupPermissions']['smwcurator']['smw-pageedit']
		);

		$this->assertTrue(
			$config['wgGroupPermissions']['smwadministrator']['smw-admin']
		);
	}

	public function testNoResetOfAlreadyRegisteredGroupPermissions() {

		// Avoid re-setting permissions, refs #1137
		$localConfig['wgGroupPermissions']['sysop']['smw-admin'] = false;
		$localConfig['wgGroupPermissions']['smwadministrator']['smw-admin'] = false;

		$localConfig = array_merge(
			$this->defaultConfig,
			$localConfig
		);

		$instance = new Setup();
		$instance->init( $localConfig, 'Foo' );

		$this->assertFalse(
			$localConfig['wgGroupPermissions']['sysop']['smw-admin']
		);

		$this->assertFalse(
			$localConfig['wgGroupPermissions']['smwadministrator']['smw-admin']
		);

	}

	public function testRegisterParamDefinitions() {

		$config = $this->defaultConfig;

		$config['wgParamDefinitions']['smwformat'] = '';

		$this->assertEmpty(
			$config['wgParamDefinitions']['smwformat']
		);

		$instance = new Setup();
		$instance->init( $config, 'Foo' );

		$this->assertNotEmpty(
			$config['wgParamDefinitions']['smwformat']
		);
	}

	public function testRegisterFooterIcon() {

		$config = $this->defaultConfig;

		$config['wgFooterIcons']['poweredby'] = [];

		$instance = new Setup();
		$instance->init( $config, 'Foo' );

		$this->assertNotEmpty(
			$config['wgFooterIcons']['poweredby']['semanticmediawiki']
		);
	}

	/**
	 * @return array
	 */
	public function specialPageDataProvider() {

		$specials = [
			'Ask',
			'Browse',
			'PageProperty',
			'SearchByProperty',
			'SMWAdmin',
			'Concepts',
			'ExportRDF',
			'Types',
			'URIResolver',
			'Properties',
			'UnusedProperties',
			'WantedProperties',
			'ProcessingErrorList',
			'PropertyLabelSimilarity'
		];

		return $this->buildDataProvider( 'wgSpecialPages', $specials, '' );
	}

	/**
	 * @return array
	 */
	public function jobClassesDataProvider() {

		$jobs = [

			'smw.update',
			'smw.refresh',
			'smw.updateDispatcher',
			'smw.fulltextSearchTableUpdate',
			'smw.entityIdDisposer',
			'smw.propertyStatisticsRebuild',
			'smw.fulltextSearchTableRebuild',
			'smw.changePropagationDispatch',
			'smw.changePropagationUpdate',
			'smw.changePropagationClassUpdate',
			'smw.elasticIndexerRecovery',
			'smw.elasticFileIngest',

			// Legacy
			'SMW\UpdateJob',
			'SMW\RefreshJob',
			'SMW\UpdateDispatcherJob',
			'SMW\FulltextSearchTableUpdateJob',
			'SMW\EntityIdDisposerJob',
			'SMW\PropertyStatisticsRebuildJob',
			'SMW\FulltextSearchTableRebuildJob',
			'SMW\ChangePropagationDispatchJob',
			'SMW\ChangePropagationUpdateJob',
			'SMW\ChangePropagationClassUpdateJob',
			'SMWUpdateJob',
			'SMWRefreshJob',
		];

		return $this->buildDataProvider( 'wgJobClasses', $jobs, '' );
	}

	/**
	 * @return array
	 */
	public function apiModulesDataProvider() {

		$modules = [
			'ask',
			'smwinfo',
			'askargs',
			'browsebysubject',
			'browsebyproperty'
		];

		return $this->buildDataProvider( 'wgAPIModules', $modules, '' );
	}

	private function assertArrayEntryExists( $target, $entry, $config, $type = 'class' ) {

		$config = $config + $this->defaultConfig;

		$this->assertEmpty(
			$config[$target][$entry],
			"Asserts that {$entry} is empty"
		);

		$instance = new Setup();
		$instance->init( $config, 'Foo' );

		$this->assertNotEmpty( $config[$target][$entry] );

		switch ( $type ) {
			case 'class':
				$this->assertTrue( class_exists( $config[$target][$entry] ) );
				break;
			case 'file':
				$this->assertTrue( file_exists( $config[$target][$entry] ) );
				break;
		}
	}

	/**
	 * @return array
	 */
	private function buildDataProvider( $id, $definitions, $default ) {

		$provider = [];

		foreach ( $definitions as $definition ) {
			$provider[] = [
				$definition,
				[ $id => [ $definition => $default ] ],
			];
		}

		return $provider;
	}

}
