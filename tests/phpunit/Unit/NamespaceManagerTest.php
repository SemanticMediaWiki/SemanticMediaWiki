<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
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
 */
class NamespaceManagerTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private array $globalsSnapshot;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->globalsSnapshot = [
			'wgLanguageCode' => $GLOBALS['wgLanguageCode'] ?? 'en',
			'wgNamespaceAliases' => $GLOBALS['wgNamespaceAliases'] ?? [],
			'wgNamespacesToBeSearchedDefault' => $GLOBALS['wgNamespacesToBeSearchedDefault'] ?? [],
			'smwgNamespacesWithSemanticLinks' => $GLOBALS['smwgNamespacesWithSemanticLinks'] ?? [],
		];
		$GLOBALS['wgLanguageCode'] = 'en';
	}

	protected function tearDown(): void {
		NamespaceManager::clear();
		foreach ( $this->globalsSnapshot as $k => $v ) {
			$GLOBALS[$k] = $v;
		}
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct(): void {
		$localLanguage = $this->getMockBuilder( LocalLanguage::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			NamespaceManager::class,
			new NamespaceManager( $localLanguage )
		);
	}

	public function testGetCanonicalNames(): void {
		$result = NamespaceManager::getCanonicalNames();
		$this->assertCount( 6, $result );
		$this->assertSame( 'Property', $result[SMW_NS_PROPERTY] );
		$this->assertSame( 'Property_talk', $result[SMW_NS_PROPERTY_TALK] );
		$this->assertSame( 'Concept', $result[SMW_NS_CONCEPT] );
		$this->assertSame( 'Concept_talk', $result[SMW_NS_CONCEPT_TALK] );
		$this->assertSame( 'smw/schema', $result[SMW_NS_SCHEMA] );
		$this->assertSame( 'smw/schema_talk', $result[SMW_NS_SCHEMA_TALK] );
	}

	/**
	 * @dataProvider canonicalNameListProvider
	 */
	public function testGetCanonicalNameList( int $key, string $expected ): void {
		$result = NamespaceManager::getCanonicalNames();
		$this->assertSame( $expected, $result[$key] );
	}

	public function testInitCanonicalNamespacesAddsCanonicalNames(): void {
		$namespaces = [];
		$this->assertTrue( NamespaceManager::initCanonicalNamespaces( $namespaces ) );
		$this->assertSame( 'Property', $namespaces[SMW_NS_PROPERTY] );
		$this->assertSame( 'Concept', $namespaces[SMW_NS_CONCEPT] );
		$this->assertSame( 'smw/schema', $namespaces[SMW_NS_SCHEMA] );
	}

	public function testInitCanonicalNamespacesEvictsCollidingExistingName(): void {
		// T160665: a third-party extension that registers e.g. id=10001 with
		// name 'Property' must be evicted so SMW can take that canonical name.
		$namespaces = [
			10001 => 'Property',
			10002 => 'Property_talk',
		];

		NamespaceManager::initCanonicalNamespaces( $namespaces );

		$this->assertArrayNotHasKey( 10001, $namespaces );
		$this->assertArrayNotHasKey( 10002, $namespaces );
		$this->assertSame( 'Property', $namespaces[SMW_NS_PROPERTY] );
		$this->assertSame( 'Property_talk', $namespaces[SMW_NS_PROPERTY_TALK] );
	}

	public function testInitCanonicalNamespacesMergesNamespaceAliases(): void {
		$namespaces = [];
		NamespaceManager::initCanonicalNamespaces( $namespaces );

		// Every canonical name flips back to its ID via wgNamespaceAliases.
		$this->assertSame(
			SMW_NS_PROPERTY,
			$GLOBALS['wgNamespaceAliases']['Property'] ?? null
		);
		$this->assertSame(
			SMW_NS_CONCEPT,
			$GLOBALS['wgNamespaceAliases']['Concept'] ?? null
		);
	}

	public function testInitCanonicalNamespacesSeedsSemanticLinkDefaults(): void {
		unset(
			$GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY],
			$GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT],
			$GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_SCHEMA]
		);

		$namespaces = [];
		NamespaceManager::initCanonicalNamespaces( $namespaces );

		$this->assertTrue( $GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY] );
		$this->assertTrue( $GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT] );
		$this->assertTrue( $GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_SCHEMA] );
	}

	public function testInitCanonicalNamespacesPreservesUserSemanticLinkOverride(): void {
		$GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY] = false;

		$namespaces = [];
		NamespaceManager::initCanonicalNamespaces( $namespaces );

		$this->assertFalse( $GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY] );
		$this->assertTrue( $GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT] );
	}

	public function testInitCanonicalNamespacesSeedsSearchDefaults(): void {
		unset(
			$GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY],
			$GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_CONCEPT]
		);

		$namespaces = [];
		NamespaceManager::initCanonicalNamespaces( $namespaces );

		$this->assertTrue( $GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY] );
		$this->assertTrue( $GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_CONCEPT] );
	}

	public function testInitCanonicalNamespacesPreservesUserSearchOverride(): void {
		$GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY] = false;

		$namespaces = [];
		NamespaceManager::initCanonicalNamespaces( $namespaces );

		$this->assertFalse( $GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY] );
		$this->assertTrue( $GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_CONCEPT] );
	}

	public function testLanguageCodeChangeAfterInitThrows(): void {
		$namespaces = [];
		NamespaceManager::initCanonicalNamespaces( $namespaces );

		$GLOBALS['wgLanguageCode'] = 'fr';
		$this->expectException( SiteLanguageChangeException::class );
		NamespaceManager::initCanonicalNamespaces( $namespaces );
	}

	public function canonicalNameListProvider(): iterable {
		yield [ SMW_NS_PROPERTY, 'Property' ];
		yield [ SMW_NS_CONCEPT, 'Concept' ];
		yield [ SMW_NS_SCHEMA, 'smw/schema' ];
	}
}
