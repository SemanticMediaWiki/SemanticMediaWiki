<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerDeferrableCompositeIndicatorProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$indicatorProviders = [];

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider',
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider',
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider',
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);
	}

	public function testIsDeferredMode() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'bool',
			$instance->isDeferredMode()
		);
	}

	public function testGetName() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'string',
			$instance->getName()
		);
	}

	public function testGetIndicators() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'array',
			$instance->getIndicators()
		);
	}

	public function testGetModules() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'array',
			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'string',
			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$deferrableIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setDeferredMode' );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->will( $this->returnValue( true ) );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->will( $this->returnValue( [] ) );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'Foo' ) );

		$indicatorProviders = [
			$deferrableIndicatorProvider
		];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_DeferredMode() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$deferrableIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setDeferredMode' );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->will( $this->returnValue( true ) );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->will( $this->returnValue( [] ) );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'Foo' ) );

		$deferrableIndicatorProvider->expects( $this->once() )
			->method( 'getIndicators' )
			->will( $this->returnValue( [ 'content' => '' ] ) );

		$indicatorProviders = [
			$deferrableIndicatorProvider
		];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$instance->setDeferredMode( true );

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

}
