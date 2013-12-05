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
}
