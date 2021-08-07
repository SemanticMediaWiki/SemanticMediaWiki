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

	private $namespaceInfo;

	protected function setUp() : void {

		$this->namespaceInfo = $this->getMockBuilder( '\SMW\MediaWiki\NamespaceInfo' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ResourceLoaderGetConfigVars::class,
			new ResourceLoaderGetConfigVars( $this->namespaceInfo )
		);
	}

	public function testProcess() {

		$vars = [];

		$instance = new ResourceLoaderGetConfigVars(
			$this->namespaceInfo
		);

		$optionKeys = ResourceLoaderGetConfigVars::OPTION_KEYS;

		foreach ( $optionKeys as $key ) {
			$instance->setOption( $key, [] );
		}

		$instance->setOption( 'smwgNamespacesWithSemanticLinks', [ NS_MAIN ] );

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
