<?php

namespace SMW\Tests\Integration\MediaWiki;

use MWNamespace;
use SMW\NamespaceManager;
use SMW\Settings;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\MwHooksHandler;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceRegistrationDBIntegrationTest extends MwDBaseUnitTestCase {

	private $mwHooksHandler;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = new MwHooksHandler();
	}

	public function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testRunNamespaceManagerWithNoConstantsDefined() {

		$this->mwHooksHandler->deregisterListedHooks();

		$default = array(
			'smwgNamespacesWithSemanticLinks' => array(),
			'wgNamespacesWithSubpages' => array(),
			'wgExtraNamespaces'  => array(),
			'wgNamespaceAliases' => array(),
			'wgLanguageCode'     => 'en'
		);

		$smwBasePath = __DIR__ . '../../../..';

		$instance = $this->getMock( '\SMW\NamespaceManager',
			array( 'isDefinedConstant' ),
			array(
				&$default,
				$smwBasePath
			)
		);

		$instance->expects( $this->any() )
			->method( 'isDefinedConstant' )
			->will( $this->returnValue( false ) );

		$this->assertTrue(
			$instance->init()
		);
	}

	public function testCanonicalNames() {

		$this->mwHooksHandler->deregisterListedHooks();

		$count = 0;
		$index = NamespaceManager::buildNamespaceIndex( Settings::newFromGlobals()->get( 'smwgNamespaceIndex' ) );
		$names = NamespaceManager::getCanonicalNames();

		$this->assertInternalType( 'array', $names );
		$this->assertInternalType( 'array', $index );

		foreach ( $index as $ns => $idx ) {

			$mwNamespace = MWNamespace::getCanonicalName( $idx );

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
