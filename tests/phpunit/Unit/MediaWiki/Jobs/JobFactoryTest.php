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

	public function testUpdateJob() {

		$instance = new JobFactory();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Jobs\UpdateJob',
			$instance->newUpdateJob( Title::newFromText( __METHOD__ ) )
		);
	}

	public function testUpdateDispatcherJob() {

		$instance = new JobFactory();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Jobs\UpdateDispatcherJob',
			$instance->newUpdateDispatcherJob( Title::newFromText( __METHOD__ ) )
		);
	}

	public function testParserCachePurgeJob() {

		$instance = new JobFactory();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Jobs\ParserCachePurgeJob',
			$instance->newParserCachePurgeJob( Title::newFromText( __METHOD__ ) )
		);
	}

}
