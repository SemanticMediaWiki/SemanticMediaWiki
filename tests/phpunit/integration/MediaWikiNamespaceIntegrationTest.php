<?php

namespace SMW\Test;

use SMW\NamespaceManager;
use SMW\Settings;
use MWNamespace;

/**
 * @covers \SMW\NamespaceManager
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
class MediaWikiNamespaceIntegrationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @since  1.9
	 */
	public function testCanonicalNames() {

		$count = 0;
		$index = NamespaceManager::buildNamespaceIndex( Settings::newFromGlobals()->get( 'smwgNamespaceIndex' ) );
		$names = NamespaceManager::getCanonicalNames();

		$this->assertInternalType( 'array', $names );
		$this->assertInternalType( 'array', $index );

		foreach ( $index as $ns => $idx ) {

			$mwNamespace = MWNamespace::getCanonicalName( $idx );

			if ( $mwNamespace ) {
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
