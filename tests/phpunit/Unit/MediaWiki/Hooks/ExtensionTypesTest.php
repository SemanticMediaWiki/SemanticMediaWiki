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

		$this->assertInstanceOf(
			ExtensionTypes::class,
			new ExtensionTypes()
		);
	}

	public function testProcess() {

		$extensionTypes = [];

		$instance = new ExtensionTypes();
		$instance->process( $extensionTypes );

		$this->assertArrayHasKey(
			'semantic',
			$extensionTypes
		);
	}

}
