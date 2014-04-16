<?php

namespace SMW\Tests\System;

use SMW\Configuration\Configuration;
use SMW\Settings;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-system
 * @group mediawiki-databaseless
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InstallationConfigurationIntegrityTest extends \PHPUnit_Framework_TestCase {

	public function testSemanticMediaWikiScriptPath() {

		$wgScriptPath   = Configuration::getInstance()->get( 'wgScriptPath' );
		$smwgScriptPath = Settings::newFromGlobals()->get( 'smwgScriptPath' );
		$expectedPath   = $wgScriptPath . '/extensions/SemanticMediaWiki';

		$this->assertTrue(
			Configuration::getInstance()->get( 'smwgScriptPath' ) === Settings::newFromGlobals()->get( 'smwgScriptPath' ),
			"Asserts that smwgScriptPath contains the expected patch"
		);

		$this->assertContains(
			'SemanticMediaWiki',
			Settings::newFromGlobals()->get( 'smwgScriptPath' ),
			"Asserts that smwgScriptPath contains SemanticMediaWiki"
		);

	}

	public function testNamespaceSettingOnExampleIfSet() {

		$expected = 'http://example.org/id/';

		if ( Configuration::getInstance()->get( 'smwgNamespace' ) !== $expected ) {
			$this->markTestSkipped( "Skip test due to missing {$expected} setting" );
		}

		$this->assertTrue(
			Configuration::getInstance()->get( 'smwgNamespace' ) === Settings::newFromGlobals()->get( 'smwgNamespace' ),
			"Asserts that smwgNamespace contains the expected {$expected}"
		);

	}

	/**
	 * @dataProvider smwgNamespacesWithSemanticLinksProvider
	 */
	public function testNamespacesWithSemanticLinksOnTravisCustomNamespace( $type, $container ) {

		if ( !defined( 'NS_TRAVIS' ) ) {
			$this->markTestSkipped( 'Test can only be executed with a specified NS_TRAVIS' );
		}

		$namespace = NS_TRAVIS;
		$extraNamespaces = Configuration::getInstance()->get( 'wgExtraNamespaces' );

		$this->assertTrue(
			isset( $extraNamespaces[$namespace] ),
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
			Configuration::getInstance()->get( 'smwgNamespacesWithSemanticLinks' )
		);

		$provider[] = array(
			'Settings',
			Settings::newFromGlobals()->get( 'smwgNamespacesWithSemanticLinks' )
		);

		return $provider;
	}

}
