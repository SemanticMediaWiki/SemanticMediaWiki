<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerIndicatorProvider;
use SMW\Indicator\IndicatorProvider;
use SMW\Localizer\MessageLocalizer;
use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\Store;
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
class ConstraintErrorEntityExaminerIndicatorProviderTest extends TestCase {

	private $store;
	private $errorLookup;
	private $messageLocalizer;
	private $entityCache;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->errorLookup = $this->getMockBuilder( ErrorLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'service' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->willReturn( $this->errorLookup );

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
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
			ConstraintErrorEntityExaminerIndicatorProvider::class,
			new ConstraintErrorEntityExaminerIndicatorProvider( $this->store, $this->entityCache )
		);

		$this->assertInstanceOf(
			IndicatorProvider::class,
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
		$subject = WikiPage::newFromText( __METHOD__ );

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

		$subject = WikiPage::newFromText( __METHOD__ );

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

		$subject = WikiPage::newFromText( __METHOD__ );

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

		$subject = WikiPage::newFromText( __METHOD__ );

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
