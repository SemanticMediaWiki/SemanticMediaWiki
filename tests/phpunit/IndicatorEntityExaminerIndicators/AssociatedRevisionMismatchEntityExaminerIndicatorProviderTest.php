<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use PHPUnit\Framework\TestCase;
use SMW\DIWikiPage;
use SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider;
use SMW\Indicator\IndicatorProvider;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\RevisionGuard;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class AssociatedRevisionMismatchEntityExaminerIndicatorProviderTest extends TestCase {

	private EntityIdManager $entityIdManager;
	private $store;
	private $errorLookup;
	private $messageLocalizer;
	private $revisionGuard;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->entityIdManager );

		$this->revisionGuard = $this->getMockBuilder( RevisionGuard::class )
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
			AssociatedRevisionMismatchEntityExaminerIndicatorProvider::class,
			new AssociatedRevisionMismatchEntityExaminerIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			IndicatorProvider::class,
			new AssociatedRevisionMismatchEntityExaminerIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			TypableSeverityIndicatorProvider::class,
			new AssociatedRevisionMismatchEntityExaminerIndicatorProvider( $this->store )
		);
	}

	public function testHasPermission() {
		$permissionExaminer = $this->getMockBuilder( PermissionExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->with( 'smw-viewentityassociatedrevisionmismatch' )
			->willReturn( true );

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertIsBool(

			$instance->hasPermission( $permissionExaminer )
		);
	}

	public function testGetName() {
		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertIsString(

			$instance->getName()
		);
	}

	public function testIsSeverityType() {
		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertIsBool(

			$instance->isSeverityType( 'foo' )
		);
	}

	public function testGetIndicators() {
		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertIsArray(

			$instance->getIndicators()
		);
	}

	public function testGetModules() {
		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertIsArray(

			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {
		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertIsString(

			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator_SameRevision() {
		$this->entityIdManager->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->willReturn( 42 );

		$this->revisionGuard->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 42 );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$instance->setDeferredMode(
			true
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertFalse(
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_DifferentRevision() {
		$this->entityIdManager->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->willReturn( 42 );

		$this->revisionGuard->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 1001 );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$instance->setDeferredMode(
			true
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertTrue(
			$instance->hasIndicator( $subject, [] )
		);

		$res = $instance->getIndicators();

		$this->assertEquals(
			'smw-entity-examiner-associated-revision-mismatch',
			$res['id']
		);
	}

	public function testPredefinedPropertyHasIndicator_DifferentRevision() {
		$this->entityIdManager->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->with( '_MDAT' )
			->willReturn( 42 );

		$this->revisionGuard->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 1001 );

		$subject = DIWikiPage::newFromText( 'Modification date', SMW_NS_PROPERTY );

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$instance->setDeferredMode(
			true
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertTrue(
			$instance->hasIndicator( $subject, [] )
		);

		$res = $instance->getIndicators();

		$this->assertEquals(
			'smw-entity-examiner-associated-revision-mismatch',
			$res['id']
		);
	}

}
