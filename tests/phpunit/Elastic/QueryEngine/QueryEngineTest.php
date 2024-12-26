<?php

namespace SMW\Tests\Elastic\QueryEngine;

use SMW\Elastic\QueryEngine\QueryEngine;
use SMW\Query\QueryResult;
use SMW\DIWikiPage;
use SMWQuery as Query;

/**
 * @covers \SMW\Elastic\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class QueryEngineTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $conditionBuilder;
	private $elasticClient;
	private $idTable;

	protected function setUp(): void {
		parent::setUp();

		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\DummyClient' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function ( $type ) use( $database ) {
			if ( $type === 'mw.db' ) {
				return $connection;
			};

			return $this->elasticClient;
		};

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryEngine::class,
			new QueryEngine( $this->store, $this->conditionBuilder )
		);
	}

	public function testgetQueryResult_MODE_NONE() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 0 );

		$query->querymode = Query::MODE_NONE;

		$instance = new QueryEngine(
			$this->store,
			$this->conditionBuilder
		);

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);
	}

	public function testgetQueryResult_MODE_INSTANCES() {
		$subject = DIWikiPage::newFromText( 'Foo' );
		$subject->setId( 42 );

		// Unknown predefined property
		$subject_predef_prop = DIWikiPage::newFromText( '_FOO', SMW_NS_PROPERTY );
		$subject_predef_prop->setId( 1001 );

		$res = [
			'hits' => [
				'hits' => [
					[ '_id' => 42 ],
					[ '_id' => 1001 ]
				],
				'max_score' => 0
			],
		];

		$list = [
			$subject,
			$subject_predef_prop
		];

		$errors = [];

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 42 );

		$query->expects( $this->any() )
			->method( 'getSortKeys' )
			->willReturn( [] );

		$this->elasticClient->expects( $this->any() )
			->method( 'search' )
			->willReturn( [ $res, $errors ] );

		$this->idTable->expects( $this->any() )
			->method( 'getDataItemsFromList' )
			->willReturn( $list );

		$this->conditionBuilder->expects( $this->once() )
			->method( 'makeFromDescription' );

		$query->querymode = Query::MODE_INSTANCES;

		$instance = new QueryEngine(
			$this->store,
			$this->conditionBuilder
		);

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);
	}

}
