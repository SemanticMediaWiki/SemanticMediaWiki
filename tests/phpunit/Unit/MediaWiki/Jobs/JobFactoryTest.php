<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\JobFactory;
use Title;

/**
 * @covers \SMW\MediaWiki\Jobs\JobFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class JobFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Jobs\JobFactory',
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
			'SMW\UpdateJob',
			'\SMW\MediaWiki\Jobs\UpdateJob'
		);

		$provider[] = array(
			'SMW\UpdateDispatcherJob',
			'\SMW\MediaWiki\Jobs\UpdateDispatcherJob'
		);

		$provider[] = array(
			'SMW\ParserCachePurgeJob',
			'\SMW\MediaWiki\Jobs\ParserCachePurgeJob'
		);

		$provider[] = array(
			'SMW\SearchTableUpdateJob',
			'\SMW\MediaWiki\Jobs\SearchTableUpdateJob'
		);

		$provider[] = array(
			'SMW\EntityIdDisposerJob',
			'\SMW\MediaWiki\Jobs\EntityIdDisposerJob'
		);

		$provider[] = array(
			'SMW\ChronologyPurgeJob',
			'\SMW\MediaWiki\Jobs\ChronologyPurgeJob'
		);

		return $provider;
	}

}
