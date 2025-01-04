<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\NamespaceManager;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\Tests\Utils\MwHooksHandler;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceInfoCanonicalNameMatchTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $mwHooksHandler;

	protected function setUp(): void {
		parent::setUp();

		$this->mwHooksHandler = new MwHooksHandler();
	}

	public function tearDown(): void {
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testRunNamespaceManagerWithNoConstantsDefined() {
		$this->mwHooksHandler->deregisterListedHooks();

		$default = [
			'smwgNamespacesWithSemanticLinks' => [],
			'wgNamespacesWithSubpages' => [],
			'wgExtraNamespaces'   => [],
			'wgNamespaceAliases'  => [],
			'wgContentNamespaces' => [],
			'wgNamespacesToBeSearchedDefault' => [],
			'wgLanguageCode'      => 'en'
		];

		$instance = $this->getMockBuilder( '\SMW\NamespaceManager' )
			->onlyMethods( [ 'isDefinedConstant' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isDefinedConstant' )
			->willReturn( false );

		$instance->init( $default );
	}

	public function testCanonicalNames() {
		$this->mwHooksHandler->deregisterListedHooks();
		$applicationFactory = ApplicationFactory::getInstance();
		$namespaceInfo = $applicationFactory->singleton( 'NamespaceInfo' );

		$count = 0;
		$index = NamespaceManager::buildNamespaceIndex( $applicationFactory->getSettings()->get( 'smwgNamespaceIndex' ) );
		$names = NamespaceManager::getCanonicalNames();

		$this->assertIsArray( $names );
		$this->assertIsArray( $index );

		foreach ( $index as $ns => $idx ) {

			$mwNamespace = $namespaceInfo->getCanonicalName( $idx );

			if ( $mwNamespace && isset( $names[$idx] ) ) {
				$this->assertEquals( $mwNamespace, $names[$idx] );
				$count++;
			}
		}

		$this->assertCount(
			$count,
			$names,
			"Asserts that expected amount of cannonical names have been verified"
		);
	}

}
