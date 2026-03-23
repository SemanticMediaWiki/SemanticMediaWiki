<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\MediaWiki\Jobs\ChangePropagationUpdateJob;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob;
use SMW\MediaWiki\Jobs\NullJob;
use SMW\MediaWiki\Jobs\ParserCachePurgeJob;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;
use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\MediaWiki\Jobs\UpdateJob;

/**
 * @covers \SMW\MediaWiki\JobFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class JobFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			JobFactory::class,
			new JobFactory()
		);
	}

	/**
	 * @dataProvider typeProvider
	 */
	public function testNewByType( $type, $expected ) {
		$instance = new JobFactory();

		$this->assertInstanceOf(
			$expected,
			$instance->newByType( $type, MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) )
		);
	}

	/**
	 * @dataProvider typeProvider
	 */
	public function testNewByTypeWithNullTitle( $type ) {
		$instance = new JobFactory();

		$this->assertInstanceOf(
			NullJob::class,
			$instance->newByType( $type, null )
		);
	}

	public function testNewByTypeOnUnknownJobThrowsException() {
		$instance = new JobFactory();

		$this->expectException( 'RuntimeException' );
		$instance->newByType( 'Foo', MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );
	}

	public function typeProvider() {
		return [
			[ 'smw.refresh', RefreshJob::class ],
			[ 'smw.update', UpdateJob::class ],
			[ 'smw.updateDispatcher', UpdateDispatcherJob::class ],
			[ 'smw.fulltextSearchTableUpdate', FulltextSearchTableUpdateJob::class ],
			[ 'smw.entityIdDisposer', EntityIdDisposerJob::class ],
			[ 'smw.propertyStatisticsRebuild', PropertyStatisticsRebuildJob::class ],
			[ 'smw.fulltextSearchTableRebuild', FulltextSearchTableRebuildJob::class ],
			[ 'smw.changePropagationDispatch', ChangePropagationDispatchJob::class ],
			[ 'smw.changePropagationUpdate', ChangePropagationUpdateJob::class ],
			[ 'smw.changePropagationClassUpdate', ChangePropagationClassUpdateJob::class ],
			[ 'smw.parserCachePurge', ParserCachePurgeJob::class ],
		];
	}

}
