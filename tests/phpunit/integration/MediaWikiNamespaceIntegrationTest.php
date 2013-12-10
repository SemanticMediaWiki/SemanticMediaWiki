<?php

namespace SMW\Test;

use SMW\NamespaceCustomizer;
use SMW\Settings;
use MWNamespace;

/**
 * @covers \SMW\NamespaceCustomizer
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

		$names   = array();

		$offset  = Settings::newFromGlobals()->get( 'smwgNamespaceIndex' );
		$result  = NamespaceCustomizer::getCanonicalNames( $names );
		$nsIndex = NamespaceCustomizer::buildCustomNamespaceIndex( $offset );

		$this->assertTrue( $result );

		foreach ( $nsIndex as $ns => $index ) {

			$mwNamespace = MWNamespace::getCanonicalName( $index );

			if ( $mwNamespace ) {
				$this->assertEquals( $mwNamespace, $names[$index] );
			}

		}

	}

}
