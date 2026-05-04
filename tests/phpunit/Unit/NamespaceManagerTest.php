<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\Exception\NamespaceIndexChangeException;
use SMW\Exception\SiteLanguageChangeException;
use SMW\Localizer\LocalLanguage\LocalLanguage;
use SMW\NamespaceManager;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\NamespaceManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceManagerTest extends TestCase {

	private $varsEnvironment;
	private $localLanguage;
	private $default;
	private $testEnvironment;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->localLanguage = $this->getMockBuilder( LocalLanguage::class )
			->disableOriginalConstructor()
			->getMock();

		$this->localLanguage->expects( $this->any() )
			->method( 'fetch' )
			->willReturnSelf();

		$this->localLanguage->expects( $this->any() )
			->method( 'getNamespaces' )
			->willReturn( [] );

		$this->localLanguage->expects( $this->any() )
			->method( 'getNamespaceAliases' )
			->willReturn( [] );

		// ConfigBootstrap::seedComputedDefaults() seeds these standard MW namespace
		// defaults before NamespaceManager::init() runs in production. Replicate
		// that here so unit tests reflect the real call order. (#6726, #6302)
		$this->default = [
			'smwgNamespacesWithSemanticLinks' => [
				NS_MAIN             => true,
				NS_TALK             => false,
				NS_USER             => true,
				NS_USER_TALK        => false,
				NS_PROJECT          => true,
				NS_PROJECT_TALK     => false,
				NS_FILE             => true,
				NS_FILE_TALK        => false,
				NS_MEDIAWIKI        => false,
				NS_MEDIAWIKI_TALK   => false,
				NS_TEMPLATE         => false,
				NS_TEMPLATE_TALK    => false,
				NS_HELP             => true,
				NS_HELP_TALK        => false,
				NS_CATEGORY         => true,
				NS_CATEGORY_TALK    => false,
			],
			'wgNamespacesWithSubpages' => [],
			'wgExtraNamespaces'  => [],
			'wgNamespaceAliases' => [],
			'wgContentNamespaces' => [],
			'wgNamespacesToBeSearchedDefault' => [],
			'wgNamespaceContentModels' => [],
			'wgLanguageCode'     => 'en'
		];
	}

	protected function tearDown(): void {
		NamespaceManager::clear();
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			NamespaceManager::class,
			new NamespaceManager( $this->localLanguage )
		);
	}

	public function testInitOnIncompleteConfiguration() {
		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => ''
		];

		$instance = new NamespaceManager( $this->localLanguage );
		$vars = $instance->init( $vars );

		$this->assertNotEmpty(
			$vars
		);
	}

	public function testGetCanonicalNames() {
		$result = NamespaceManager::getCanonicalNames();

		$this->assertIsArray(

			$result
		);

		$this->assertCount(
			6,
			$result
		);
	}

	/**
	 * @dataProvider canonicalNameListProvider
	 */
	public function testGetCanonicalNameList( $key, $expected ) {
		$result = NamespaceManager::getCanonicalNames();

		$this->assertEquals(
			$expected,
			$result[$key]
		);
	}

	public function testGetCanonicalNamesWithTypeNamespace() {
		$result = NamespaceManager::getCanonicalNames();

		$this->assertIsArray(

			$result
		);

		$this->assertCount(
			6,
			$result
		);
	}

	public function testBuildNamespaceIndex() {
		$this->assertIsArray(

			NamespaceManager::buildNamespaceIndex( 100 )
		);
	}

	public function testInitCustomNamespace() {
		$vars = [
			'wgLanguageCode' => 'en',
			'wgContentNamespaces' => []
		];

		$vars = NamespaceManager::initCustomNamespace( $vars )['newVars'];

		$this->assertNotEmpty( $vars );

		$this->assertEquals(
			100,
			$vars['smwgNamespaceIndex']
		);
	}

	public function testInitCustomNamespaceOnDifferentLanguage_ThrowsException() {
		$vars = [
			'wgLanguageCode' => 'en',
			'wgContentNamespaces' => []
		];

		NamespaceManager::initCustomNamespace( $vars );

		$vars = [
			'wgLanguageCode' => 'fr',
			'wgContentNamespaces' => []
		];

		$this->expectException( SiteLanguageChangeException::class );
		NamespaceManager::initCustomNamespace( $vars );
	}

	public function testInitCustomNamespaceWithDefaultDifferentNamespaceIndex_ThrowsException() {
		$vars = [
			'wgLanguageCode' => 'en',
			'wgContentNamespaces' => []
		];

		NamespaceManager::initCustomNamespace( $vars );

		$vars = [
			'wgLanguageCode' => 'en',
			'wgContentNamespaces' => [],
			'smwgNamespaceIndex' => 2001
		];

		$this->expectException( NamespaceIndexChangeException::class );
		NamespaceManager::initCustomNamespace( $vars );
	}

	public function testInitCustomNamespaceWithPresetDifferentNamespaceIndex_ThrowsException() {
		$vars = [
			'wgLanguageCode' => 'en',
			'wgContentNamespaces' => [],
			'smwgNamespaceIndex' => 2000
		];

		NamespaceManager::initCustomNamespace( $vars );

		$vars = [
			'wgLanguageCode' => 'en',
			'wgContentNamespaces' => [],
			'smwgNamespaceIndex' => 2001
		];

		$this->expectException( NamespaceIndexChangeException::class );
		NamespaceManager::initCustomNamespace( $vars );
	}

	public function testNamespacesInitWithEmptySettings() {
		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => ''
		];

		$instance = new NamespaceManager( $this->localLanguage );
		$vars = $instance->init( $vars );

		// SMW-internal namespaces
		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_SCHEMA]
		);

		// Standard MW namespaces seeded by ConfigBootstrap::seedComputedDefaults() (#6302)
		$this->assertArrayHasKey(
			NS_MAIN,
			$vars['smwgNamespacesWithSemanticLinks']
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][NS_MAIN]
		);

		$this->assertArrayHasKey(
			NS_HELP,
			$vars['smwgNamespacesWithSemanticLinks']
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][NS_HELP]
		);
	}

	public function testInitToKeepPreInitSettings() {
		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => '',
		];

		$vars['smwgNamespacesWithSemanticLinks'] = [
			SMW_NS_PROPERTY => false
		];

		$instance = new NamespaceManager( $this->localLanguage );
		$vars = $instance->init( $vars );

		$this->assertFalse(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT]
		);
	}

	public function testInitMergesStandardDefaultsWhenUserSetsOnlyCustomNamespaces() {
		// Simulates a LocalSettings.php that opts a custom namespace into
		// semantic processing. ConfigBootstrap::seedComputedDefaults() runs
		// first (array_plus), so both the custom entry and the standard MW
		// namespace defaults are present by the time NamespaceManager::init()
		// is called. (#6726, #6302)
		$customNamespace = 3000;

		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => '',
		];

		// Merge custom namespace on top of the already-seeded defaults,
		// reflecting the state after ConfigBootstrap has run.
		$vars['smwgNamespacesWithSemanticLinks'][$customNamespace] = true;

		$instance = new NamespaceManager( $this->localLanguage );
		$vars = $instance->init( $vars );

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][$customNamespace]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][NS_MAIN]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][NS_HELP]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);
	}

	public function testInitCustomNamespace_NamespaceAliases() {
		$localLanguage = $this->getMockBuilder( LocalLanguage::class )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->any() )
			->method( 'fetch' )
			->willReturnSelf();

		$localLanguage->expects( $this->any() )
			->method( 'getNamespaces' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getNamespaceAliases' )
			->willReturn( [ 'Foo' => SMW_NS_PROPERTY ] );

		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => '',
		];

		$vars = NamespaceManager::initCustomNamespace(
			$vars,
			$localLanguage
		)['newVars'];

		$this->assertArrayHasKey(
			'Foo',
			$vars['wgNamespaceAliases']
		);
	}

	public function testInitWithoutOverridingUserSettingsOnExtraNamespaceSettings() {
		$vars = [
			'wgNamespacesWithSubpages' => [
				SMW_NS_PROPERTY => false
			],
			'wgNamespacesToBeSearchedDefault' => [
				SMW_NS_PROPERTY => false
			],
			'wgContentNamespaces' => []
		] + $this->default;

		$instance = new NamespaceManager( $this->localLanguage );
		$vars = $instance->init( $vars );

		$this->assertFalse(
			$vars['wgNamespacesWithSubpages'][SMW_NS_PROPERTY]
		);

		$this->assertFalse(
			$vars['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY]
		);

		$this->assertContains(
			SMW_NS_PROPERTY,
			$vars['wgContentNamespaces']
		);
	}

	public function testInit_wgNamespaceContentModels() {
		$vars = $this->default;

		$instance = new NamespaceManager( $this->localLanguage );
		$vars = $instance->init( $vars );

		$this->assertEquals(
			CONTENT_MODEL_SMW_SCHEMA,
			$vars['wgNamespaceContentModels'][SMW_NS_SCHEMA]
		);
	}

	public function testInitCanonicalNamespacesWithForcedNsReset() {
		$namespaces = [
			10001 => 'Property',
			10002 => 'Property_talk'
		];

		$this->assertTrue(
			NamespaceManager::initCanonicalNamespaces( $namespaces )
		);

		$this->assertEquals(
			'Property',
			$namespaces[SMW_NS_PROPERTY]
		);

		$this->assertEquals(
			'Property_talk',
			$namespaces[SMW_NS_PROPERTY_TALK]
		);
	}

	public function canonicalNameListProvider() {
		yield [
			SMW_NS_PROPERTY,
			'Property'
		];

		yield [
			SMW_NS_CONCEPT,
			'Concept'
		];

		yield [
			SMW_NS_SCHEMA,
			'smw/schema'
		];
	}

}
