<?php

namespace SMW\Tests\Indicator;

use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicatorsFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerIndicatorsFactoryTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $entityCache;
	private $testEnvironment;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'HookDispatcher', $hookDispatcher );
		$this->testEnvironment->registerObject( 'EntityCache', $this->entityCache );
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstructEntityExaminerIndicatorProvider() {

		$instance = new EntityExaminerIndicatorsFactory();

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider',
			$instance->newEntityExaminerIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider',
			$instance->newEntityExaminerIndicatorProvider( $this->store )
		);
	}

	public function testCanConstructAssociatedRevisionMismatchEntityExaminerIndicatorProvider() {

		$instance = new EntityExaminerIndicatorsFactory();

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider',
			$instance->newAssociatedRevisionMismatchEntityExaminerIndicatorProvider( $this->store )
		);
	}

	public function testCanConstructConstraintErrorEntityExaminerIndicatorProvider() {

		$instance = new EntityExaminerIndicatorsFactory();

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerDeferrableIndicatorProvider',
			$instance->newConstraintErrorEntityExaminerIndicatorProvider( $this->store, $this->entityCache )
		);
	}

	public function testCanConstructEntityExaminerDeferrableCompositeIndicatorProvider() {

		$instance = new EntityExaminerIndicatorsFactory();

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider',
			$instance->newEntityExaminerDeferrableCompositeIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider',
			$instance->newEntityExaminerDeferrableCompositeIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider',
			$instance->newEntityExaminerDeferrableCompositeIndicatorProvider( $this->store )
		);
	}

	public function testCanConstructEntityExaminerCompositeIndicatorProvider() {

		$instance = new EntityExaminerIndicatorsFactory();
		$indicatorProviders = [];

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider',
			$instance->newEntityExaminerCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider',
			$instance->newEntityExaminerCompositeIndicatorProvider( $indicatorProviders )
		);
	}

	public function testConfirmAllCanConstructMethodsWereCalled() {

		// Available class methods to be tested
		$classMethods = get_class_methods( EntityExaminerIndicatorsFactory::class );

		// Match all "testCanConstruct" to define the expected set of methods
		$testMethods = preg_grep('/^testCanConstruct/', get_class_methods( $this ) );

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
