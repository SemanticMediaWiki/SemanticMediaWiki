<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\ChangePropagationClassUpdateJob;
use SMW\ChangePropagationDispatchJob;
use SMW\ChangePropagationUpdateJob;
use SMW\EntityIdDisposerJob;
use SMW\FulltextSearchTableRebuildJob;
use SMW\FulltextSearchTableUpdateJob;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\NullJob;
use SMW\MediaWiki\Jobs\ParserCachePurgeJob;
use SMW\PropertyStatisticsRebuildJob;
use SMW\RefreshJob;
use SMW\UpdateDispatcherJob;
use SMW\UpdateJob;

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
		$provider[] = [
			RefreshJob::class,
			'\SMW\MediaWiki\Jobs\RefreshJob'
		];

		$provider[] = [
			'smw.refresh',
			'\SMW\MediaWiki\Jobs\RefreshJob'
		];

		$provider[] = [
			UpdateJob::class,
			'\SMW\MediaWiki\Jobs\UpdateJob'
		];

		$provider[] = [
			'smw.update',
			'\SMW\MediaWiki\Jobs\UpdateJob'
		];

		$provider[] = [
			UpdateDispatcherJob::class,
			'\SMW\MediaWiki\Jobs\UpdateDispatcherJob'
		];

		$provider[] = [
			'smw.updateDispatcher',
			'\SMW\MediaWiki\Jobs\UpdateDispatcherJob'
		];

		$provider[] = [
			FulltextSearchTableUpdateJob::class,
			'\SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob'
		];

		$provider[] = [
			'smw.fulltextSearchTableUpdate',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob'
		];

		$provider[] = [
			EntityIdDisposerJob::class,
			'\SMW\MediaWiki\Jobs\EntityIdDisposerJob'
		];

		$provider[] = [
			'smw.entityIdDisposer',
			'\SMW\MediaWiki\Jobs\EntityIdDisposerJob'
		];

		$provider[] = [
			PropertyStatisticsRebuildJob::class,
			'\SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob'
		];

		$provider[] = [
			'smw.propertyStatisticsRebuild',
			'\SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob'
		];

		$provider[] = [
			FulltextSearchTableRebuildJob::class,
			'\SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob'
		];

		$provider[] = [
			'smw.fulltextSearchTableRebuild',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob'
		];

		$provider[] = [
			ChangePropagationDispatchJob::class,
			'\SMW\MediaWiki\Jobs\ChangePropagationDispatchJob'
		];

		$provider[] = [
			'smw.changePropagationDispatch',
			'\SMW\MediaWiki\Jobs\ChangePropagationDispatchJob'
		];

		$provider[] = [
			ChangePropagationUpdateJob::class,
			'\SMW\MediaWiki\Jobs\ChangePropagationUpdateJob'
		];

		$provider[] = [
			'smw.changePropagationUpdate',
			'\SMW\MediaWiki\Jobs\ChangePropagationUpdateJob'
		];

		$provider[] = [
			ChangePropagationClassUpdateJob::class,
			'\SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob'
		];

		$provider[] = [
			'smw.changePropagationClassUpdate',
			'\SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob'
		];

		$provider[] = [
			'smw.parserCachePurge',
			ParserCachePurgeJob::class
		];

		return $provider;
	}

}
