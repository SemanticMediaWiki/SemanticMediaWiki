<?php

namespace SMW\Tests\Unit;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Language\Language;
use PHPUnit\Framework\TestCase;
use SMW\Setup;
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
	private $hookContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$language = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();

		$this->defaultConfig = [
			'smwgMainCacheType' => CACHE_NONE,
			'smwgNamespacesWithSemanticLinks' => [],
			'smwgEnableUpdateJobs' => false,
			'wgNamespacesWithSubpages' => [],
			'wgExtensionAssetsPath'    => false,
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

	public function testHookRunOnSetupAfterInitializationComplete() {
		$this->hookContainer->expects( $this->once() )
			->method( 'run' )
			->with( 'SMW::Setup::AfterInitializationComplete', $this->isType( 'array' ) );

		$config = $this->defaultConfig;

		$instance = new Setup();

		$instance->setHookContainer(
			$this->hookContainer
		);

		$instance->init( $config, '' );
	}

	public function testRegisterParamDefinitions() {
		$config = $this->defaultConfig;

		$config['wgParamDefinitions']['smwformat'] = '';

		$this->assertEmpty(
			$config['wgParamDefinitions']['smwformat']
		);

		$instance = new Setup();

		$instance->setHookContainer(
			$this->hookContainer
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

		$instance->setHookContainer(
			$this->hookContainer
		);

		$config = $instance->init( $config, 'Foo' );

		$this->assertNotEmpty(
			$config['wgFooterIcons']['poweredbysmw']['semanticmediawiki']
		);
	}

}
