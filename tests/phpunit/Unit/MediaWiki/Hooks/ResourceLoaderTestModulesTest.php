<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ResourceLoaderTestModules;

/**
 * @covers \SMW\MediaWiki\Hooks\ResourceLoaderTestModules
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ResourceLoaderTestModulesTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$resourceLoader = $this->getMockBuilder( '\ResourceLoader' )
			->disableOriginalConstructor()
			->getMock();

		$testModules = array();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ResourceLoaderTestModules',
			new ResourceLoaderTestModules( $resourceLoader, $testModules, '', '' )
		);
	}

	public function testProcess() {

		$resourceLoader = $this->getMockBuilder( '\ResourceLoader' )
			->disableOriginalConstructor()
			->getMock();

		$testModules = array();

		$instance = new ResourceLoaderTestModules( $resourceLoader, $testModules, '', '' );
		$instance->process();

		$this->assertArrayHasKey( 'ext.smw.tests', $testModules['qunit'] );
	}

}
