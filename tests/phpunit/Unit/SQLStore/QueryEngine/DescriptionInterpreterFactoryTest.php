<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionInterpreterFactoryTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $connection;
	private $circularReferenceGuard;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
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

		$conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionInterpreterFactory(
			$this->store,
			$this->circularReferenceGuard
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\DescriptionInterpreters\DispatchingDescriptionInterpreter',
			$instance->newDispatchingDescriptionInterpreter( $conditionBuilder )
		);
	}

}
