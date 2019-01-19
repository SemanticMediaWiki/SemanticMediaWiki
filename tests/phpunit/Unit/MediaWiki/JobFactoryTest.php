<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\JobFactory;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\JobFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class JobFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

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
			$instance->newByType( $type, Title::newFromText( __METHOD__ ) )
		);
	}

	/**
	 * @dataProvider typeProvider
	 */
	public function testNewByTypeWithNullTitle( $type ) {

		$instance = new JobFactory();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Jobs\NullJob',
			$instance->newByType( $type, null )
		);
	}

	public function testNewByTypeOnUnknownJobThrowsException() {

		$instance = new JobFactory();

		$this->setExpectedException( 'RuntimeException' );
		$instance->newByType( 'Foo', Title::newFromText( __METHOD__ ) );
	}

	public function typeProvider() {

		$provider[] = [
			'SMW\RefreshJob',
			'\SMW\MediaWiki\Jobs\RefreshJob'
		];

		$provider[] = [
			'smw.refresh',
			'\SMW\MediaWiki\Jobs\RefreshJob'
		];

		$provider[] = [
			'SMW\UpdateJob',
			'\SMW\MediaWiki\Jobs\UpdateJob'
		];

		$provider[] = [
			'smw.update',
			'\SMW\MediaWiki\Jobs\UpdateJob'
		];

		$provider[] = [
			'SMW\UpdateDispatcherJob',
			'\SMW\MediaWiki\Jobs\UpdateDispatcherJob'
		];

		$provider[] = [
			'smw.updateDispatcher',
			'\SMW\MediaWiki\Jobs\UpdateDispatcherJob'
		];

		$provider[] = [
			'SMW\ParserCachePurgeJob',
			'\SMW\MediaWiki\Jobs\NullJob'
		];

		$provider[] = [
			'smw.parserCachePurge',
			'\SMW\MediaWiki\Jobs\NullJob'
		];

		$provider[] = [
			'SMW\FulltextSearchTableUpdateJob',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob'
		];

		$provider[] = [
			'smw.fulltextSearchTableUpdate',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob'
		];

		$provider[] = [
			'SMW\EntityIdDisposerJob',
			'\SMW\MediaWiki\Jobs\EntityIdDisposerJob'
		];

		$provider[] = [
			'smw.entityIdDisposer',
			'\SMW\MediaWiki\Jobs\EntityIdDisposerJob'
		];

		$provider[] = [
			'SMW\PropertyStatisticsRebuildJob',
			'\SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob'
		];

		$provider[] = [
			'smw.propertyStatisticsRebuild',
			'\SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob'
		];

		$provider[] = [
			'SMW\FulltextSearchTableRebuildJob',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob'
		];

		$provider[] = [
			'smw.fulltextSearchTableRebuild',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob'
		];

		$provider[] = [
			'SMW\ChangePropagationDispatchJob',
			'\SMW\MediaWiki\Jobs\ChangePropagationDispatchJob'
		];

		$provider[] = [
			'smw.changePropagationDispatch',
			'\SMW\MediaWiki\Jobs\ChangePropagationDispatchJob'
		];

		$provider[] = [
			'SMW\ChangePropagationUpdateJob',
			'\SMW\MediaWiki\Jobs\ChangePropagationUpdateJob'
		];

		$provider[] = [
			'smw.changePropagationUpdate',
			'\SMW\MediaWiki\Jobs\ChangePropagationUpdateJob'
		];

		$provider[] = [
			'SMW\ChangePropagationClassUpdateJob',
			'\SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob'
		];

		$provider[] = [
			'smw.changePropagationClassUpdate',
			'\SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob'
		];

		return $provider;
	}

}
