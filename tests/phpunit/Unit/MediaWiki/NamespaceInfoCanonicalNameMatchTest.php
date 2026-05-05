<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\NamespaceManager;
use SMW\Tests\Utils\MwHooksHandler;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceInfoCanonicalNameMatchTest extends TestCase {

	private $mwHooksHandler;

	protected function setUp(): void {
		parent::setUp();
		$this->mwHooksHandler = new MwHooksHandler();
	}

	protected function tearDown(): void {
		$this->mwHooksHandler->restoreListedHooks();
		parent::tearDown();
	}

	public function testCanonicalNames(): void {
		$this->mwHooksHandler->deregisterListedHooks();
		$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();

		$names = NamespaceManager::getCanonicalNames();
		$this->assertIsArray( $names );

		$count = 0;
		foreach ( $names as $idx => $name ) {
			$mwNamespace = $namespaceInfo->getCanonicalName( $idx );
			if ( $mwNamespace ) {
				$this->assertEquals( $mwNamespace, $name );
				$count++;
			}
		}

		$this->assertCount(
			$count,
			$names,
			'Asserts that the expected number of canonical names have been verified'
		);
	}

	public function testCanonicalNamesAvailableWithoutBootstrap(): void {
		$this->assertCount( 6, NamespaceManager::getCanonicalNames() );
	}
}
