<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\Parser\ParserOutput;
use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\PostProcHandlerFactory;
use SMW\PostProcHandler;
use SMW\Settings;

/**
 * @covers \SMW\MediaWiki\PostProcHandlerFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class PostProcHandlerFactoryTest extends TestCase {

	private Cache $cache;
	private Settings $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->cache = $this->createMock( Cache::class );
		$this->settings = $this->createMock( Settings::class );
	}

	private function newInstance(): PostProcHandlerFactory {
		return new PostProcHandlerFactory( $this->cache, $this->settings );
	}

	public function testCanConstruct(): void {
		$this->assertInstanceOf(
			PostProcHandlerFactory::class,
			$this->newInstance()
		);
	}

	public function testNewForReturnsConfiguredHandler(): void {
		$this->settings->method( 'get' )
			->willReturnMap( [
				[ 'smwgPostEditUpdate', [ 'check' => true ] ],
				[ 'smwgEnabledQueryDependencyLinksStore', false ],
				[ 'smwgEnabledFulltextSearch', false ],
			] );

		$parserOutput = $this->createMock( ParserOutput::class );

		$handler = $this->newInstance()->newFor( $parserOutput );

		$this->assertInstanceOf( PostProcHandler::class, $handler );
	}

}
