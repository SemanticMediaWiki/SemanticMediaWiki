<?php

namespace SMW\Tests\Integration;

use SMW\Test\MwIntegrationTestCase;

use SMW\NamespaceManager;
use SMW\ExtensionContext;

use SMW\Settings;
use MWNamespace;

/**
 * @covers \SMW\NamespaceManager
 *
 * @ingroup Test
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
class MwNamespaceIntegrationTest extends MwIntegrationTestCase {

	public function testRunNamespaceManagerWithNoConstantsDefined() {

		$default = array(
			'smwgNamespacesWithSemanticLinks' => array(),
			'wgNamespacesWithSubpages' => array(),
			'wgExtraNamespaces'  => array(),
			'wgNamespaceAliases' => array(),
			'wgLanguageCode'     => 'en'
		);

		$smwBasePath = __DIR__ . '../../../..';

		$instance = $this->getMock( '\SMW\NamespaceManager',
			array( 'assertConstantIsDefined' ),
			array(
				&$default,
				$smwBasePath
			)
		);

		$instance->expects( $this->any() )
			->method( 'assertConstantIsDefined' )
			->will( $this->returnValue( false ) );

		$this->assertTrue( $instance->run() );
	}

	public function testCanonicalNames() {

		$this->runExtensionSetup( new ExtensionContext );

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
