<?php

namespace SMW\Tests;

use SMW\NamespaceManager;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\NamespaceManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceManagerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $varsEnvironment;
	private $localLanguage;
	private $default;

	protected function setUp() : void {
		$this->testEnvironment = new TestEnvironment();

		$this->localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$this->localLanguage->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnSelf() );

		$this->localLanguage->expects( $this->any() )
			->method( 'getNamespaces' )
			->will( $this->returnValue( [] ) );

		$this->localLanguage->expects( $this->any() )
			->method( 'getNamespaceAliases' )
			->will( $this->returnValue( [] ) );

		$this->default = [
			'smwgNamespacesWithSemanticLinks' => [],
			'wgNamespacesWithSubpages' => [],
			'wgExtraNamespaces'  => [],
			'wgNamespaceAliases' => [],
			'wgContentNamespaces' => [],
			'wgNamespacesToBeSearchedDefault' => [],
			'wgNamespaceContentModels' => [],
			'wgLanguageCode'     => 'en'
		];
	}

	protected function tearDown() : void {
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
		$newVars = $instance->init( $vars );

		$this->assertNotEmpty(
			$newVars
		);
	}

	public function testGetCanonicalNames() {

		$result = NamespaceManager::getCanonicalNames();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertCount(
			8,
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

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertCount(
			8,
			$result
		);
	}

	public function testBuildNamespaceIndex() {
		$this->assertInternalType(
			'array',
			NamespaceManager::buildNamespaceIndex( 100 )
		);
	}

	public function testInitCustomNamespace() {

		$vars = [
			'wgLanguageCode' => 'en',
			'wgContentNamespaces' => []
		];

		$newVars = NamespaceManager::initCustomNamespace( $vars )['newVars'];

		$this->assertNotEmpty( $newVars );

		$this->assertEquals(
			100,
			$newVars['smwgNamespaceIndex']
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

		$this->expectException( '\SMW\Exception\SiteLanguageChangeException' );
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

		$this->expectException( '\SMW\Exception\NamespaceIndexChangeException' );
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

		$this->expectException( '\SMW\Exception\NamespaceIndexChangeException' );
		NamespaceManager::initCustomNamespace( $vars );
	}

	public function testNamespacesInitWithEmptySettings() {

		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => ''
		];

		$instance = new NamespaceManager( $this->localLanguage );
		$newVars = $instance->init( $vars );

		$this->assertTrue(
			$newVars['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);

		$this->assertTrue(
			$newVars['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT]
		);

		$this->assertTrue(
			$newVars['smwgNamespacesWithSemanticLinks'][SMW_NS_SCHEMA]
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
		$newVars = $instance->init( $vars );

		$this->assertFalse(
			$newVars['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);

		$this->assertTrue(
			$newVars['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT]
		);
	}

	public function testInitCustomNamespace_NamespaceAliases() {

		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnSelf() );

		$localLanguage->expects( $this->any() )
			->method( 'getNamespaces' )
			->will( $this->returnValue( [] ) );

		$localLanguage->expects( $this->once() )
			->method( 'getNamespaceAliases' )
			->will( $this->returnValue( [ 'Foo' => SMW_NS_PROPERTY ] ) );

		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => '',
		];

		$newVars = NamespaceManager::initCustomNamespace(
			$vars,
			$localLanguage
		)['newVars'];

		$this->assertArrayHasKey(
			'Foo',
			$newVars['wgNamespaceAliases']
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
			'wgContentNamespaces' => [
				SMW_NS_PROPERTY => false
			]
		] + $this->default;

		$instance = new NamespaceManager( $this->localLanguage );
		$newVars = $instance->init( $vars );

		$this->assertFalse(
			$newVars['wgNamespacesWithSubpages'][SMW_NS_PROPERTY]
		);

		$this->assertFalse(
			$newVars['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY]
		);

		$this->assertFalse(
			$newVars['wgContentNamespaces'][SMW_NS_PROPERTY]
		);
	}

	public function testInit_wgNamespaceContentModels() {

		$vars = $this->default;

		$instance = new NamespaceManager( $this->localLanguage );
		$newVars = $instance->init( $vars );

		$this->assertEquals(
			CONTENT_MODEL_SMW_SCHEMA,
			$newVars['wgNamespaceContentModels'][SMW_NS_SCHEMA]
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

		yield [
			SMW_NS_RULE,
			'Rule'
		];
	}

}
