<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\ParserCachePurgeJob;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;

/**
 * @covers \SMW\MediaWiki\Jobs\ParserCachePurgeJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ParserCachePurgeJobTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\ParserCachePurgeJob',
			new ParserCachePurgeJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testJobWithIdList( $parameters ) {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ParserCachePurgeJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function parametersProvider() {

		$provider[] = array(
			'idlist' => array( 1, 2 )
		);

		$provider[] = array(
			'idlist' => '1|2'
		);

		return $provider;
	}

}
