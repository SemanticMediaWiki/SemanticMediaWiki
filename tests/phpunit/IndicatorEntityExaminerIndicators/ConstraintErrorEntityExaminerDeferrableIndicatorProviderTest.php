<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerDeferrableIndicatorProvider;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerDeferrableIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConstraintErrorEntityExaminerDeferrableIndicatorProviderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $errorLookup;
	private $messageLocalizer;
	private $entityCache;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->errorLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ErrorLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection', 'service' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->willReturn( $this->errorLookup );

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer->expects( $this->any() )
			->method( 'msg' )
			->willReturn( 'foo' );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerDeferrableIndicatorProvider',
			new ConstraintErrorEntityExaminerDeferrableIndicatorProvider( $this->store, $this->entityCache )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider',
			new ConstraintErrorEntityExaminerDeferrableIndicatorProvider( $this->store, $this->entityCache )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProvider',
			new ConstraintErrorEntityExaminerDeferrableIndicatorProvider( $this->store, $this->entityCache )
		);
	}

	public function testHasIndicator_NoDeferredMode() {
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->setDeferredMode( false );

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertFalse(
			$instance->isDeferredMode()
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);

		$this->assertEquals(
			[ 'id' => 'smw-entity-examiner-deferred-constraint-error' ],
			$instance->getIndicators()
		);
	}

	public function testHasIndicator_FromErrorLookup() {
		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$this->entityCache->expects( $this->once() )
			->method( 'save' );

		$this->entityCache->expects( $this->once() )
			->method( 'associate' );

		$this->errorLookup->expects( $this->once() )
			->method( 'buildArray' )
			->willReturn( [ 'Foo' ] );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->setDeferredMode( true );

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_FromCache() {
		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( [ 'Foo' ] );

		$this->errorLookup->expects( $this->never() )
			->method( 'buildArray' );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerDeferrableIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->setDeferredMode( true );

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

}
