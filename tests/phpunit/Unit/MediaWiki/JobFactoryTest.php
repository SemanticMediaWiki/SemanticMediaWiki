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

		$provider[] = array(
			'SMW\RefreshJob',
			'\SMW\MediaWiki\Jobs\RefreshJob'
		);

		$provider[] = array(
			'smw.refresh',
			'\SMW\MediaWiki\Jobs\RefreshJob'
		);

		$provider[] = array(
			'SMW\UpdateJob',
			'\SMW\MediaWiki\Jobs\UpdateJob'
		);

		$provider[] = array(
			'smw.update',
			'\SMW\MediaWiki\Jobs\UpdateJob'
		);

		$provider[] = array(
			'SMW\UpdateDispatcherJob',
			'\SMW\MediaWiki\Jobs\UpdateDispatcherJob'
		);

		$provider[] = array(
			'smw.updateDispatcher',
			'\SMW\MediaWiki\Jobs\UpdateDispatcherJob'
		);

		$provider[] = array(
			'SMW\ParserCachePurgeJob',
			'\SMW\MediaWiki\Jobs\ParserCachePurgeJob'
		);

		$provider[] = array(
			'smw.parserCachePurge',
			'\SMW\MediaWiki\Jobs\ParserCachePurgeJob'
		);

		$provider[] = array(
			'SMW\FulltextSearchTableUpdateJob',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob'
		);

		$provider[] = array(
			'smw.fulltextSearchTableUpdate',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob'
		);

		$provider[] = array(
			'SMW\EntityIdDisposerJob',
			'\SMW\MediaWiki\Jobs\EntityIdDisposerJob'
		);

		$provider[] = array(
			'smw.entityIdDisposer',
			'\SMW\MediaWiki\Jobs\EntityIdDisposerJob'
		);

		$provider[] = array(
			'SMW\PropertyStatisticsRebuildJob',
			'\SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob'
		);

		$provider[] = array(
			'smw.propertyStatisticsRebuild',
			'\SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob'
		);

		$provider[] = array(
			'SMW\FulltextSearchTableRebuildJob',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob'
		);

		$provider[] = array(
			'smw.fulltextSearchTableRebuild',
			'\SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob'
		);

		$provider[] = array(
			'SMW\ChangePropagationDispatchJob',
			'\SMW\MediaWiki\Jobs\ChangePropagationDispatchJob'
		);

		$provider[] = array(
			'smw.changePropagationDispatch',
			'\SMW\MediaWiki\Jobs\ChangePropagationDispatchJob'
		);

		$provider[] = array(
			'SMW\ChangePropagationUpdateJob',
			'\SMW\MediaWiki\Jobs\ChangePropagationUpdateJob'
		);

		$provider[] = array(
			'smw.changePropagationUpdate',
			'\SMW\MediaWiki\Jobs\ChangePropagationUpdateJob'
		);

		$provider[] = array(
			'SMW\ChangePropagationClassUpdateJob',
			'\SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob'
		);

		$provider[] = array(
			'smw.changePropagationClassUpdate',
			'\SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob'
		);

		return $provider;
	}

}
