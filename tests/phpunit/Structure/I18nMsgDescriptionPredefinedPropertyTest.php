<?php

namespace SMW\Tests\Structure;

use SMW\TypesRegistry;

/**
 * @group semantic-mediawiki
 * @group system-test
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class I18nMsgDescriptionPredefinedPropertyTest extends \PHPUnit_Framework_TestCase {

	const MSG_KEY_PREFIX = 'smw-property-predefined';

	/**
	 * @dataProvider predefinePropertiesProvider
	 */
	public function testCheckPredefinedPropertyDesriptionKey( $key ) {

		$contents = json_decode(
			file_get_contents( $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] . '/en.json' ),
			true
		);

		$msgKey = self::MSG_KEY_PREFIX . (
			str_replace( '_', '-', strtolower( $key ) )
		);

		$this->assertArrayHasKey(
			$msgKey,
			$contents,
			"Failed to find an appropriate `$msgKey` message key\n" .
			"in `en.json` for the `$key` predefined property.\n"
		);
	}

	public function predefinePropertiesProvider() {
		foreach ( TypesRegistry::getPropertyList() as $key => $value ) {
			yield [ $key ];
		}
	}

}
