<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\DispatchingDescriptionInterpreter;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CircularReferenceGuard;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionInterpreterFactoryTest extends TestCase {

	private $store;
	private $connection;
	private $circularReferenceGuard;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->circularReferenceGuard = $this->getMockBuilder( CircularReferenceGuard::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DescriptionInterpreterFactory::class,
			new DescriptionInterpreterFactory( $this->store, $this->circularReferenceGuard )
		);
	}

	public function testCanConstructDispatchingDescriptionInterpreter() {
		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionInterpreterFactory(
			$this->store,
			$this->circularReferenceGuard
		);

		$this->assertInstanceOf(
			DispatchingDescriptionInterpreter::class,
			$instance->newDispatchingDescriptionInterpreter( $conditionBuilder )
		);
	}

}
