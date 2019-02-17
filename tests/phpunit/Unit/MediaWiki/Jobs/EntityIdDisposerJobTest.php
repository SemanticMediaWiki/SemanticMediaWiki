<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\Tests\TestEnvironment;

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
	private $connection;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->getMockForAbstractClass();

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

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
			EntityIdDisposerJob::class,
			new EntityIdDisposerJob( $title )
		);
	}

	public function testCanConstructOutdatedEntitiesResultIterator() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdDisposerJob( $title );

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->newOutdatedEntitiesResultIterator()
		);
	}

	public function testCanConstructOutdatedQueryLinksResultIterator() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdDisposerJob( $title );

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->newOutdatedQueryLinksResultIterator()
		);
	}

	public function testCanConstructUnassignedQueryLinksResultIterator() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdDisposerJob( $title );

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->newUnassignedQueryLinksResultIterator()
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testJobRun( $parameters ) {

		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => NS_MAIN,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sort' => '',
			'smw_sortkey' => '',
			'smw_hash' => ''
		];

		$this->connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

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

		$provider[] = [
			[]
		];

		$provider[] = [
			[ 'id' => 42 ]
		];

		return $provider;
	}

}
