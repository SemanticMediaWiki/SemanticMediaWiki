<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Jobs\PageUpdaterFactory;
use SMW\MediaWiki\PageUpdater;
use SMW\Services\ServicesFactory;

/**
 * @covers \SMW\MediaWiki\Jobs\PageUpdaterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class PageUpdaterFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PageUpdaterFactory::class,
			new PageUpdaterFactory( $this->createMock( ServicesFactory::class ) )
		);
	}

	public function testNewPageUpdaterDelegatesToServicesFactory() {
		$pageUpdater = $this->createMock( PageUpdater::class );

		$servicesFactory = $this->createMock( ServicesFactory::class );
		$servicesFactory->expects( $this->once() )
			->method( 'newPageUpdater' )
			->willReturn( $pageUpdater );

		$instance = new PageUpdaterFactory( $servicesFactory );

		$this->assertSame(
			$pageUpdater,
			$instance->newPageUpdater()
		);
	}

}
