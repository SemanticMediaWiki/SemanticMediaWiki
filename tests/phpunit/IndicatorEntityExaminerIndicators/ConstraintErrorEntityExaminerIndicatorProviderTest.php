<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\DIWikiPage;
use SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerIndicatorProvider;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ConstraintErrorEntityExaminerIndicatorProviderTest extends \PHPUnit\Framework\TestCase {

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
			->setMethods( [ 'getConnection', 'service' ] )
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
			'\SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerIndicatorProvider',
			new ConstraintErrorEntityExaminerIndicatorProvider( $this->store, $this->entityCache )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProvider',
			new ConstraintErrorEntityExaminerIndicatorProvider( $this->store, $this->entityCache )
		);
	}

	public function testGetName() {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$this->assertIsString(

			$instance->getName()
		);
	}

	public function testGetIndicators() {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$this->assertIsArray(

			$instance->getIndicators()
		);
	}

	public function testGetModules() {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$this->assertIsArray(

			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$this->assertIsString(

			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator_DisabledCheck() {
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->setConstraintErrorCheck(
			false
		);

		$this->assertFalse(
			$instance->hasIndicator( $subject, [] )
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

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_FromErrorLookup_NoErrors() {
		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$this->entityCache->expects( $this->once() )
			->method( 'save' );

		$this->entityCache->expects( $this->once() )
			->method( 'associate' );

		$this->errorLookup->expects( $this->once() )
			->method( 'buildArray' )
			->willReturn( [] );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->hasIndicator( $subject, [] );

		$this->assertEquals(
			[
				'id' => 'smw-entity-examiner-deferred-constraint-error',
				'content' => ''
			],
			$instance->getIndicators()
		);
	}

	public function testHasIndicator_FromCache() {
		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( [ 'Foo' ] );

		$this->errorLookup->expects( $this->never() )
			->method( 'buildArray' );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

}
