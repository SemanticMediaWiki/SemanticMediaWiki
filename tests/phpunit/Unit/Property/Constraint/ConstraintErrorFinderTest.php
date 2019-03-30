<?php

namespace SMW\Tests\Property\Constraint;

use SMW\Property\Constraint\ConstraintErrorFinder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Constraint\ConstraintErrorFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintErrorFinderTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $connection;
	private $query;
	private $tableDefinition;

	protected function setUp() {

		$this->tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$this->query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $this->query ) );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintErrorFinder::class,
			new ConstraintErrorFinder( $this->store )
		);
	}

	public function testFindConstraintErrors() {

		$this->query->expects( $this->once() )
			->method( 'execute' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '_foo' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $this->tableDefinition ] ) );

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();


		$instance = new ConstraintErrorFinder(
			$this->store
		);

		$instance->findConstraintErrors( $subject );
	}

}
