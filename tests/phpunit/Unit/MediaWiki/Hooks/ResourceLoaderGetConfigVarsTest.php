<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars;

/**
 * @covers \SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ResourceLoaderGetConfigVarsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ResourceLoaderGetConfigVars::class,
			new ResourceLoaderGetConfigVars()
		);
	}

	public function testProcess() {

		$vars = [];

		$instance = new ResourceLoaderGetConfigVars();
		$instance->process( $vars );

		$this->assertArrayHasKey(
			'smw-config',
			$vars
		);

		$this->assertArrayHasKey(
			'namespaces',
			$vars['smw-config']
		);

		$this->assertArrayHasKey(
			'settings',
			$vars['smw-config']
		);
	}

}
