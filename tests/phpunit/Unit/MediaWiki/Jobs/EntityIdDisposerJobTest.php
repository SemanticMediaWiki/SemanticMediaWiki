<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;

/**
 * @covers \SMW\MediaWiki\Jobs\EntityIdDisposerJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EntityIdDisposerJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( 'Foo' ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->getMockForAbstractClass();

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->setConnectionManager( $connectionManager );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\EntityIdDisposerJob',
			new EntityIdDisposerJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testJobRun( $parameters ) {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new EntityIdDisposerJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function parametersProvider() {

		$provider[] = array(
			array()
		);

		$provider[] = array(
			array( 'id' => 42 )
		);

		return $provider;
	}

}
