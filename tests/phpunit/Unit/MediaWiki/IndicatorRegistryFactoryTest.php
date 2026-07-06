<?php

namespace SMW\Tests\Unit\MediaWiki;

use PHPUnit\Framework\TestCase;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\MediaWiki\IndicatorRegistry;
use SMW\MediaWiki\IndicatorRegistryFactory;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\IndicatorRegistryFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class IndicatorRegistryFactoryTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private EntityExaminerIndicatorsFactory $entityExaminerIndicatorsFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->entityExaminerIndicatorsFactory = $this->createMock( EntityExaminerIndicatorsFactory::class );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance(): IndicatorRegistryFactory {
		return new IndicatorRegistryFactory( $this->entityExaminerIndicatorsFactory );
	}

	public function testCanConstruct(): void {
		$this->assertInstanceOf(
			IndicatorRegistryFactory::class,
			$this->newInstance()
		);
	}

	public function testNewForReturnsEmptyRegistryWhenEntityExaminerNotRequested(): void {
		$this->entityExaminerIndicatorsFactory->expects( $this->never() )
			->method( 'newEntityExaminerIndicatorProvider' );

		$registry = $this->newInstance()->newFor( false );

		$this->assertInstanceOf( IndicatorRegistry::class, $registry );
	}

	public function testNewForAttachesEntityExaminerProviderWhenRequested(): void {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$provider = $this->createMock( EntityExaminerCompositeIndicatorProvider::class );

		$this->entityExaminerIndicatorsFactory->expects( $this->once() )
			->method( 'newEntityExaminerIndicatorProvider' )
			->with( $store )
			->willReturn( $provider );

		$registry = $this->newInstance()->newFor( true );

		$this->assertInstanceOf( IndicatorRegistry::class, $registry );
	}

}
