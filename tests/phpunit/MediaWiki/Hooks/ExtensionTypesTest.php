<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ExtensionTypes;

/**
 * @covers \SMW\MediaWiki\Hooks\ExtensionTypes
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ExtensionTypesTest extends \PHPUnit\Framework\TestCase {

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
