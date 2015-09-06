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

		$vars = array();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars',
			new ResourceLoaderGetConfigVars( $vars )
		);
	}

	public function testProcess() {

		$vars = array();

		$instance = new ResourceLoaderGetConfigVars( $vars );
		$instance->process();

		$this->assertArrayHasKey( 'smw-config', $vars );
	}

}
