<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Title\NamespaceInfo;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars;
use SMW\Settings;

/**
 * @covers \SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ResourceLoaderGetConfigVarsTest extends TestCase {

	private $namespaceInfo;
	private $settings;

	protected function setUp(): void {
		$this->namespaceInfo = $this->getMockBuilder( NamespaceInfo::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = $this->createMock( Settings::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ResourceLoaderGetConfigVars::class,
			new ResourceLoaderGetConfigVars( $this->namespaceInfo, $this->settings )
		);
	}

	public function testProcess() {
		$vars = [];

		$this->settings->method( 'get' )
			->willReturnMap( [
				[ 'smwgQMaxLimit', 100 ],
				[ 'smwgQMaxInlineLimit', 50 ],
				[ 'smwgNamespacesWithSemanticLinks', [ NS_MAIN => true ] ],
				[ 'smwgResultFormats', [] ],
			] );

		$instance = new ResourceLoaderGetConfigVars(
			$this->namespaceInfo,
			$this->settings
		);

		$config = $this->createMock( Config::class );

		$instance->onResourceLoaderGetConfigVars( $vars, '', $config );

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
