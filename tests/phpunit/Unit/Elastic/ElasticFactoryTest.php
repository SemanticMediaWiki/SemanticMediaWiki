<?php

namespace SMW\Tests\Elastic;

use SMW\Elastic\ElasticFactory;

/**
 * @covers \SMW\Elastic\ElasticFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticFactoryTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $outputFormatter;
	private $conditionBuilder;
	private $connection;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ElasticFactory::class,
			new ElasticFactory()
		);
	}

	public function testCanConstructConfig() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Config',
			$instance->newConfig()
		);
	}

	public function testCanConstructConnectionProvider() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Connection\ConnectionProvider',
			$instance->newConnectionProvider()
		);
	}

	public function testCanConstructIndexer() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Indexer',
			$instance->newIndexer( $this->store )
		);
	}

	public function testCanConstructFileIndexer() {

		$indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\FileIndexer',
			$instance->newFileIndexer( $indexer )
		);
	}

	public function testCanConstructRollover() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Rollover',
			$instance->newRollover( $this->connection )
		);
	}

	public function testCanConstructBulk() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Bulk',
			$instance->newBulk( $this->connection )
		);
	}

	public function testCanConstructQueryEngine() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->newQueryEngine( $this->store )
		);
	}

	public function testCanConstructRebuilder() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Rebuilder',
			$instance->newRebuilder( $this->store )
		);
	}

	public function testCanConstructInfoTaskHandler() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Admin\ElasticClientTaskHandler',
			$instance->newInfoTaskHandler( $this->store, $this->outputFormatter )
		);
	}

	public function testCanConstructConceptDescriptionInterpreter() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter',
			$instance->newConceptDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructSomePropertyInterpreter() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter',
			$instance->newSomePropertyInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructSomeValueInterpreter() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\SomeValueInterpreter',
			$instance->newSomeValueInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructClassDescriptionInterpreter() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter',
			$instance->newClassDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructNamespaceDescriptionInterpreter() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter',
			$instance->newNamespaceDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructValueDescriptionInterpreter() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter',
			$instance->newValueDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructConjunctionInterpreter() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter',
			$instance->newConjunctionInterpreter( $this->conditionBuilder )
		);
	}

	public function testCanConstructDisjunctionInterpreter() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter',
			$instance->newDisjunctionInterpreter( $this->conditionBuilder )
		);
	}

	public function testOnEntityReferenceCleanUpComplete() {

		$instance = new ElasticFactory();

		$this->assertTrue(
			$instance->onEntityReferenceCleanUpComplete( $this->store, 42, null, false )
		);
	}

	public function testOnTaskHandlerFactory() {

		$instance = new ElasticFactory();
		$taskHandlers = [];
		$outputFormatter = null;

		$this->assertTrue(
			$instance->onTaskHandlerFactory( $taskHandlers, $this->store, $outputFormatter, null )
		);
	}

}
