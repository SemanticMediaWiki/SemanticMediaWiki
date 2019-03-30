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

		$this->assertInstanceOf(
			ResourceLoaderTestModules::class,
			new ResourceLoaderTestModules( $resourceLoader )
		);
	}

	public function testProcess() {

		$resourceLoader = $this->getMockBuilder( '\ResourceLoader' )
			->disableOriginalConstructor()
			->getMock();

		$testModules = [];

		$instance = new ResourceLoaderTestModules( $resourceLoader );
		$instance->process( $testModules );

		$this->assertArrayHasKey(
			'ext.smw.tests',
			$testModules['qunit']
		);

		$this->assertArrayHasKey(
			'localBasePath',
			$testModules['qunit']['ext.smw.tests']
		);

		$this->assertArrayHasKey(
			'remoteExtPath',
			$testModules['qunit']['ext.smw.tests']
		);
	}

}
