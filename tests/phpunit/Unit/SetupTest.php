<?php

namespace SMW\Tests\Unit;

use MediaWiki\Language\Language;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\HookDispatcher;
use SMW\Setup;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Setup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SetupTest extends TestCase {

	private $testEnvironment;
	private $defaultConfig;
	private $hookDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->willReturn( [] );

		$language = $this->getMockBuilder( Language::class )
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
			'wgLanguageCode'    => 'en',
			'wgLang'            => $language,
			'IP'                => 'Foo',
			'smwgConfigFileDir' => '',
			'smwgUpgradeKey' => '',
			'smwgIgnoreUpgradeKeyCheck' => true
		];

		$this->testEnvironment = new TestEnvironment( $this->defaultConfig );
		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Setup::class,
			new Setup()
		);
	}

	public function testRegisterExtensionCheck() {
		$vars = [
			'smwgIgnoreExtensionRegistrationCheck' => true
		];

		Setup::registerExtensionCheck( $vars );

		$this->assertCount(
			1,
			$vars
		);

		$vars = [
			'smwgIgnoreExtensionRegistrationCheck' => false
		];

		Setup::registerExtensionCheck( $vars );

		$this->assertCount(
			2,
			$vars
		);
	}

	public function testResourceModules() {
		$config = $this->defaultConfig;

		$instance = new Setup();

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$config = $instance->init( $config, '' );

		$this->assertNotEmpty(
			$config['wgResourceModules']
		);
	}

	public function testHookRunOnSetupAfterInitializationComplete() {
		$this->hookDispatcher->expects( $this->once() )
			->method( 'onSetupAfterInitializationComplete' );

		$config = $this->defaultConfig;

		$instance = new Setup();

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->init( $config, '' );
	}

	public function testRegisterDefaultRightsUserGroupPermissions() {
		$config = $this->defaultConfig;

		$instance = new Setup();

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$config = $instance->init( $config, 'Foo' );

		$this->assertNotEmpty(
			$config['wgAvailableRights']
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

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$localConfig = $instance->init( $localConfig, 'Foo' );

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

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$config = $instance->init( $config, 'Foo' );

		$this->assertNotEmpty(
			$config['wgParamDefinitions']['smwformat']
		);
	}

	public function testRegisterFooterIcon() {
		$config = $this->defaultConfig;

		$config['wgFooterIcons']['poweredbysmw'] = [];

		$instance = new Setup();

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$config = $instance->init( $config, 'Foo' );

		$this->assertNotEmpty(
			$config['wgFooterIcons']['poweredbysmw']['semanticmediawiki']
		);
	}

}
