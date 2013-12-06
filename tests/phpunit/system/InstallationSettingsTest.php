<?php

namespace SMW\Test;

use SMW\Settings;

/**
 * Sanity checks after the installation
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InstallationSettingsTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @since 1.9
	 */
	public function testSemanticMediaWikiScriptPath() {

		$wgScriptPath   = $GLOBALS['wgScriptPath'];
		$smwgScriptPath = Settings::newFromGlobals()->get( 'smwgScriptPath' );
		$expectedPath   = $wgScriptPath . '/extensions/SemanticMediaWiki';

		$this->assertEquals(
			$expectedPath,
			$smwgScriptPath,
			"Asserts that smwgScriptPath contains an expected path, with wgScriptPath being {$wgScriptPath}"
		);

	}

	/**
	 * @since 1.9
	 */
	public function testNamespaceSettingOnExampleIfSet() {

		$expected = 'http://example.org/id/';

		if ( $GLOBALS['smwgNamespace'] !== $expected ) {
			$this->markTestSkipped( "Skip test due to missing {$expected} setting" );
		}

		$this->assertTrue(
			$GLOBALS['smwgNamespace'] === Settings::newFromGlobals()->get( 'smwgNamespace' ),
			"Asserts that smwgNamespace contains the expected {$expected}"
		);

	}

	/**
	 * @dataProvider smwgNamespacesWithSemanticLinksProvider
	 *
	 * @since 1.9
	 */
	public function testNamespacesWithSemanticLinksOnTravisCustomNamespace( $type, $container ) {

		if ( !defined( 'NS_TRAVIS' ) ) {
			$this->markTestSkipped( 'Test can only be executed with a specified NS_TRAVIS' );
		}

		$namespace = NS_TRAVIS;

		$this->assertTrue(
			isset( $GLOBALS['wgExtraNamespaces'][$namespace] ),
			"Asserts that wgExtraNamespaces contains the expected {$namespace} NS"
		);

		$foundNamespaceEntry = false;

		foreach ( $container as $key => $value ) {
			if ( $key === $namespace ) {
				$foundNamespaceEntry = true;
				break;
			}
		}

		$this->assertTrue(
			$foundNamespaceEntry,
			"Asserts that smwgNamespacesWithSemanticLinks retrieved from {$type} contains the expected {$namespace} NS"
		);

	}

	/**
	 * @since 1.9
	 */
	public function smwgNamespacesWithSemanticLinksProvider() {

		$provider = array();

		$provider[] = array(
			'GLOBALS',
			$GLOBALS['smwgNamespacesWithSemanticLinks']
		);

		$provider[] = array(
			'Settings',
			Settings::newFromGlobals()->get( 'smwgNamespacesWithSemanticLinks' )
		);

		return $provider;
	}


}
