<?php

namespace SMW\Tests;

use SMW\NamespaceManager;

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

	private $varsEnvironment;
	private $lang;
	private $default;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();

		$this->lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$this->lang->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnSelf() );

		$this->lang->expects( $this->any() )
			->method( 'getNamespaces' )
			->will( $this->returnValue( [] ) );

		$this->lang->expects( $this->any() )
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

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			NamespaceManager::class,
			new NamespaceManager( $this->lang )
		);
	}

	public function testInitOnIncompleteConfiguration() {

		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => ''
		];

		$instance = new NamespaceManager( $this->lang );
		$instance->init( $vars );

		$this->assertNotEmpty(
			$vars
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

		NamespaceManager::initCustomNamespace( $vars );

		$this->assertNotEmpty( $vars );

		$this->assertEquals(
			100,
			$vars['smwgNamespaceIndex']
		);
	}

	public function testNamespacesInitWithEmptySettings() {

		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => ''
		];

		$instance = new NamespaceManager( $this->lang );
		$instance->init( $vars );

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_SCHEMA]
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

		$instance = new NamespaceManager( $this->lang );
		$instance->init( $vars );

		$this->assertFalse(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);

		$this->assertTrue(
			$vars['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT]
		);
	}

	public function testInitCustomNamespace_NamespaceAliases() {

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnSelf() );

		$lang->expects( $this->any() )
			->method( 'getNamespaces' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getNamespaceAliases' )
			->will( $this->returnValue( [ 'Foo' => SMW_NS_PROPERTY ] ) );

		$vars = $this->default + [
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => '',
		];

		$instance = NamespaceManager::initCustomNamespace(
			$vars,
			$lang
		);

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
			'wgContentNamespaces' => [
				SMW_NS_PROPERTY => false
			]
		] + $this->default;

		$instance = new NamespaceManager( $this->lang );
		$instance->init( $vars );

		$this->assertFalse(
			$vars['wgNamespacesWithSubpages'][SMW_NS_PROPERTY]
		);

		$this->assertFalse(
			$vars['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY]
		);

		$this->assertFalse(
			$vars['wgContentNamespaces'][SMW_NS_PROPERTY]
		);
	}

	public function testInit_wgNamespaceContentModels() {

		$vars = $this->default;

		$instance = new NamespaceManager( $this->lang );
		$instance->init( $vars );

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

		yield [
			SMW_NS_RULE,
			'Rule'
		];
	}

}
