<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EventsServicesContainerBuildTest extends \PHPUnit_Framework_TestCase {

	private $callbackContainerFactory;
	private $servicesFileDir;
	private $propertySpecificationLookup;
	private $resultCache;
	private $entityCache;

	protected function setUp() : void {
		parent::setUp();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->resultCache = $this->getMockBuilder( '\SMW\Query\Cache\ResultCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->callbackContainerFactory = new CallbackContainerFactory();
		$this->servicesFileDir = $GLOBALS['smwgServicesFileDir'];
	}

	/**
	 * @dataProvider servicesProvider
	 */
	public function testCanConstruct( $service, $parameters, $expected ) {

		array_unshift( $parameters, $service );

		$containerBuilder = $this->callbackContainerFactory->newCallbackContainerBuilder();

		$containerBuilder->registerObject( 'ResultCache', $this->resultCache );
		$containerBuilder->registerObject( 'EntityCache', $this->entityCache );
		$containerBuilder->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );

		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'events.php' );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( [ $containerBuilder, 'create' ], $parameters )
		);
	}

	public function servicesProvider() {

		$provider[] = [
			'InvalidateResultCacheEventListener',
			[],
			'\SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener'
		];

		$provider[] = [
			'InvalidateEntityCacheEventListener',
			[],
			'\SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener'
		];

		$provider[] = [
			'InvalidatePropertySpecificationLookupCacheEventListener',
			[],
			'\SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener'
		];

		return $provider;
	}
}
