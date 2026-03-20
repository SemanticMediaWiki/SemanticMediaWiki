<?php

namespace SMW\Tests\Unit\Indicator;

use PHPUnit\Framework\TestCase;
use SMW\EntityCache;
use SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerDeferrableIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\MediaWiki\HookDispatcher;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicatorsFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerIndicatorsFactoryTest extends TestCase {

	private $store;
	private $entityCache;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'HookDispatcher', $hookDispatcher );
		$this->testEnvironment->registerObject( 'EntityCache', $this->entityCache );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstructEntityExaminerIndicatorProvider() {
		$instance = new EntityExaminerIndicatorsFactory();

		$this->assertInstanceOf(
			EntityExaminerCompositeIndicatorProvider::class,
			$instance->newEntityExaminerIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			CompositeIndicatorProvider::class,
			$instance->newEntityExaminerIndicatorProvider( $this->store )
		);
	}

	public function testCanConstructAssociatedRevisionMismatchEntityExaminerIndicatorProvider() {
		$instance = new EntityExaminerIndicatorsFactory();

		$this->assertInstanceOf(
			AssociatedRevisionMismatchEntityExaminerIndicatorProvider::class,
			$instance->newAssociatedRevisionMismatchEntityExaminerIndicatorProvider( $this->store )
		);
	}

	public function testCanConstructConstraintErrorEntityExaminerIndicatorProvider() {
		$instance = new EntityExaminerIndicatorsFactory();

		$this->assertInstanceOf(
			ConstraintErrorEntityExaminerDeferrableIndicatorProvider::class,
			$instance->newConstraintErrorEntityExaminerIndicatorProvider( $this->store, $this->entityCache )
		);
	}

	public function testCanConstructEntityExaminerDeferrableCompositeIndicatorProvider() {
		$instance = new EntityExaminerIndicatorsFactory();

		$this->assertInstanceOf(
			EntityExaminerDeferrableCompositeIndicatorProvider::class,
			$instance->newEntityExaminerDeferrableCompositeIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			DeferrableIndicatorProvider::class,
			$instance->newEntityExaminerDeferrableCompositeIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			CompositeIndicatorProvider::class,
			$instance->newEntityExaminerDeferrableCompositeIndicatorProvider( $this->store )
		);
	}

	public function testCanConstructEntityExaminerCompositeIndicatorProvider() {
		$instance = new EntityExaminerIndicatorsFactory();
		$indicatorProviders = [];

		$this->assertInstanceOf(
			EntityExaminerCompositeIndicatorProvider::class,
			$instance->newEntityExaminerCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			CompositeIndicatorProvider::class,
			$instance->newEntityExaminerCompositeIndicatorProvider( $indicatorProviders )
		);
	}

	public function testConfirmAllCanConstructMethodsWereCalled() {
		// Available class methods to be tested
		$classMethods = get_class_methods( EntityExaminerIndicatorsFactory::class );

		// Match all "testCanConstruct" to define the expected set of methods
		$testMethods = preg_grep( '/^testCanConstruct/', get_class_methods( $this ) );

		$testMethods = array_flip(
			str_replace( 'testCanConstruct', 'new', $testMethods )
		);

		foreach ( $classMethods as $name ) {

			if ( substr( $name, 0, 3 ) !== 'new' ) {
				continue;
			}

			$this->assertArrayHasKey( $name, $testMethods );
		}
	}

}
