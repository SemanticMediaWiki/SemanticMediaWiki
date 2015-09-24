<?php

namespace SMW\Tests;

use SMW\Setup;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\Setup
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SetupTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $defaultConfig;
	private $mockbuilder;

	protected function setUp() {
		parent::setUp();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array() ) );

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $store );

		$this->defaultConfig = array(
			'smwgCacheType' => CACHE_NONE,
			'smwgNamespacesWithSemanticLinks' => array(),
			'smwgEnableUpdateJobs' => false,
			'wgNamespacesWithSubpages' => array(),
			'wgExtensionAssetsPath'    => false,
			'wgResourceModules' => array(),
			'wgScriptPath'      => '/Foo',
			'wgServer'          => 'http://example.org',
			'wgVersion'         => '1.21',
			'wgLanguageCode'    => 'en',
			'wgLang'            => $language,
			'IP'                => 'Foo'
		);

		foreach ( $this->defaultConfig as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$applicationFactory = $this->getMockBuilder( '\SMW\ApplicationFactory' )
			->disableOriginalConstructor()
			->getMock();

		$config = array();
		$basepath = 'Foo';

		$this->assertInstanceOf(
			'\SMW\Setup',
			new Setup( $applicationFactory, $config, $basepath )
		);
	}

	public function testResourceModules() {

		$config   = $this->defaultConfig;
		$basepath = $this->applicationFactory->getSettings()->get( 'smwgIP' );

		$instance = new Setup( $this->applicationFactory, $config, $basepath );
		$instance->run();

		$this->assertNotEmpty(
			$config['wgResourceModules']
		);
	}

	/**
	 * @dataProvider apiModulesDataProvider
	 */
	public function testRegisterApiModules( $moduleEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgAPIModules', $moduleEntry, $setup );
	}

	/**
	 * @dataProvider jobClassesDataProvider
	 */
	public function testRegisterJobClasses( $jobEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgJobClasses', $jobEntry, $setup );
	}

	/**
	 * @dataProvider messagesFilesDataProvider
	 */
	public function testRegisterMessageFiles( $moduleEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgExtensionMessagesFiles', $moduleEntry, $setup, 'file' );
	}

	/**
	 * @dataProvider specialPageDataProvider
	 */
	public function testRegisterSpecialPages( $specialEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgSpecialPages', $specialEntry, $setup );
	}

	public function testRegisterDefaultRightsUserGroupPermissions() {

		$config = $this->defaultConfig;

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

		$this->assertNotEmpty(
			$config['wgAvailableRights']
		);

		$this->assertTrue(
			$config['wgGroupPermissions']['sysop']['smw-admin']
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

		$instance = new Setup( $this->applicationFactory, $localConfig, 'Foo' );
		$instance->run();

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

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

		$this->assertNotEmpty(
			$config['wgParamDefinitions']['smwformat']
		);
	}

	public function testRegisterFooterIcon() {

		$config = $this->defaultConfig;

		$config['wgFooterIcons']['poweredby'] = array();

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

		$this->assertNotEmpty(
			$config['wgFooterIcons']['poweredby']['semanticmediawiki']
		);
	}

	/**
	 * @return array
	 */
	public function specialPageDataProvider() {

		$specials = array(
			'Ask',
			'Browse',
			'PageProperty',
			'SearchByProperty',
			'SMWAdmin',
			'SemanticStatistics',
			'Concepts',
			'ExportRDF',
			'Types',
			'URIResolver',
			'Properties',
			'UnusedProperties',
			'WantedProperties',
			'DeferredRequestDispatcher'
		);

		return $this->buildDataProvider( 'wgSpecialPages', $specials, '' );
	}

	/**
	 * @return array
	 */
	public function jobClassesDataProvider() {

		$jobs = array(
			'SMW\UpdateJob',
			'SMW\RefreshJob',
			'SMW\UpdateDispatcherJob',
			'SMW\DeleteSubjectJob',

			// Legacy
			'SMWUpdateJob',
			'SMWRefreshJob',
		);

		return $this->buildDataProvider( 'wgJobClasses', $jobs, '' );
	}

	/**
	 * @return array
	 */
	public function apiModulesDataProvider() {

		$modules = array(
			'ask',
			'smwinfo',
			'askargs',
			'browsebysubject',
		);

		return $this->buildDataProvider( 'wgAPIModules', $modules, '' );
	}


	/**
	 * @return array
	 */
	public function messagesFilesDataProvider() {

		$modules = array(
			'SemanticMediaWiki',
			'SemanticMediaWikiAlias',
			'SemanticMediaWikiMagic',
			'SemanticMediaWikiNamespaces'
		);

		return $this->buildDataProvider( 'wgExtensionMessagesFiles', $modules, '' );
	}

	private function assertArrayEntryExists( $target, $entry, $config, $type = 'class' ) {

		$config = $config + $this->defaultConfig;

		$this->assertEmpty(
			$config[$target][$entry],
			"Asserts that {$entry} is empty"
		);

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

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

		$provider = array();

		foreach ( $definitions as $definition ) {
			$provider[] = array(
				$definition,
				array( $id => array( $definition => $default ) ),
			);
		}

		return $provider;
	}

}
