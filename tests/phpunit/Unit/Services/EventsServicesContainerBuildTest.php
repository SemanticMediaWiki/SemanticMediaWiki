<?php

namespace SMW\Tests\Unit\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use PHPUnit\Framework\TestCase;
use SMW\EntityCache;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Property\SpecificationLookup;
use SMW\Query\Cache\ResultCache;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class EventsServicesContainerBuildTest extends TestCase {

	private $callbackContainerFactory;
	private $servicesFileDir;
	private $propertySpecificationLookup;
	private $resultCache;
	private $entityCache;

	protected function setUp(): void {
		parent::setUp();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->resultCache = $this->getMockBuilder( ResultCache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
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
			InvalidateResultCacheEventListener::class
		];

		$provider[] = [
			'InvalidateEntityCacheEventListener',
			[],
			InvalidateEntityCacheEventListener::class
		];

		$provider[] = [
			'InvalidatePropertySpecificationLookupCacheEventListener',
			[],
			InvalidatePropertySpecificationLookupCacheEventListener::class
		];

		return $provider;
	}
}
