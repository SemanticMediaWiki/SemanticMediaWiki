<?php

namespace SMW\Tests;

use SMW\NamespaceManager;
use SMW\Tests\TestEnvironment;
use SMW\ExtraneousLanguage;

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

	private $testEnvironment;
	private $extraneousLanguage;
	private $default;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();

		$this->extraneousLanguage = $this->getMockBuilder( '\SMW\ExtraneousLanguage\ExtraneousLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$this->extraneousLanguage->expects( $this->any() )
			->method( 'fetchByLanguageCode' )
			->will( $this->returnSelf() );

		$this->extraneousLanguage->expects( $this->any() )
			->method( 'getNamespaces' )
			->will( $this->returnValue( array() ) );

		$this->extraneousLanguage->expects( $this->any() )
			->method( 'getNamespaceAliases' )
			->will( $this->returnValue( array() ) );

		$this->default = array(
			'smwgNamespacesWithSemanticLinks' => array(),
			'wgNamespacesWithSubpages' => array(),
			'wgExtraNamespaces'  => array(),
			'wgNamespaceAliases' => array(),
			'wgContentNamespaces' => array(),
			'wgNamespacesToBeSearchedDefault' => array(),
			'wgLanguageCode'     => 'en'
		);
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\NamespaceManager',
			new NamespaceManager( $test, $this->extraneousLanguage )
		);
	}

	public function testInitOnIncompleteConfiguration() {

		$test = $this->default + array(
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => ''
		);

		$instance = new NamespaceManager( $test, $this->extraneousLanguage );
		$instance->init();

		$this->assertNotEmpty(
			$test
		);
	}

	public function testGetCanonicalNames() {

		$this->testEnvironment->addConfiguration(
			'smwgHistoricTypeNamespace',
			false
		);

		$result = NamespaceManager::getCanonicalNames();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertCount(
			4,
			$result
		);
	}

	public function testGetCanonicalNamesWithTypeNamespace() {

		$this->testEnvironment->addConfiguration(
			'smwgHistoricTypeNamespace',
			true
		);

		$result = NamespaceManager::getCanonicalNames();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertCount(
			6,
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

		$test = array(
			'wgLanguageCode' => 'en',
			'wgContentNamespaces' => array()
		);

		NamespaceManager::initCustomNamespace( $test );

		$this->assertNotEmpty( $test );
		$this->assertEquals(
			100,
			$test['smwgNamespaceIndex']
		);
	}

	public function testNamespacesInitWithEmptySettings() {

		$this->testEnvironment->addConfiguration(
			'smwgHistoricTypeNamespace',
			false
		);

		$test = $this->default + array(
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => ''
		);

		$instance = new NamespaceManager( $test, $this->extraneousLanguage );
		$instance->init();

		$this->assertTrue(
			$test['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);

		$this->assertTrue(
			$test['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT]
		);

		$this->assertFalse(
			isset( $test['smwgNamespacesWithSemanticLinks'][SMW_NS_TYPE] )
		);
	}

	public function testInitToKeepPreInitSettings() {

		$this->testEnvironment->addConfiguration(
			'smwgHistoricTypeNamespace',
			true
		);

		$test = $this->default + array(
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => '',
		);

		$test['smwgNamespacesWithSemanticLinks'] = array(
			SMW_NS_PROPERTY => false
		);

		$instance = new NamespaceManager( $test, $this->extraneousLanguage );
		$instance->init();

		$this->assertFalse(
			$test['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY]
		);

		$this->assertTrue(
			$test['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT]
		);

		$this->assertTrue(
			$test['smwgNamespacesWithSemanticLinks'][SMW_NS_TYPE]
		);
	}

	public function testInitWithoutOverridingUserSettingsOnExtraNamespaceSettings() {

		$test = array(
			'wgNamespacesWithSubpages' => array(
				SMW_NS_PROPERTY => false
			),
			'wgNamespacesToBeSearchedDefault' => array(
				SMW_NS_PROPERTY => false
			),
			'wgContentNamespaces' => array(
				SMW_NS_PROPERTY => false
			)
		) + $this->default;

		$instance = new NamespaceManager( $test, $this->extraneousLanguage );
		$instance->init();

		$this->assertFalse(
			$test['wgNamespacesWithSubpages'][SMW_NS_PROPERTY]
		);

		$this->assertFalse(
			$test['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY]
		);

		$this->assertFalse(
			$test['wgContentNamespaces'][SMW_NS_PROPERTY]
		);
	}

	public function testInitCanonicalNamespacesWithForcedNsReset() {

		$namespaces = array(
			10001 => 'Property',
			10002 => 'Property_talk'
		);

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

}
