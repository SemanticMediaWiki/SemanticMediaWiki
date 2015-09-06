<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ExtensionTypes;

/**
 * @covers \SMW\MediaWiki\Hooks\ExtensionTypes
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ExtensionTypesTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$extensionTypes = array();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ExtensionTypes',
			new ExtensionTypes( $extensionTypes )
		);
	}

	public function testProcess() {

		$extensionTypes = array();

		$instance = new ExtensionTypes( $extensionTypes );
		$instance->process();

		$this->assertArrayHasKey( 'semantic', $extensionTypes );
	}

}
