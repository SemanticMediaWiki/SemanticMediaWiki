<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class AssociatedRevisionMismatchEntityExaminerIndicatorProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $errorLookup;
	private $messageLocalizer;
	private $revisionGuard;
	private $testEnvironment;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->entityIdManager ) );

		$this->revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer->expects( $this->any() )
			->method( 'msg' )
			->will( $this->returnValue( 'foo' ) );
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider',
			new AssociatedRevisionMismatchEntityExaminerIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProvider',
			new AssociatedRevisionMismatchEntityExaminerIndicatorProvider( $this->store )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider',
			new AssociatedRevisionMismatchEntityExaminerIndicatorProvider( $this->store )
		);
	}

	public function testHasPermission() {

		$permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->with( $this->equalTo( 'smw-viewentityassociatedrevisionmismatch' ) )
			->will( $this->returnValue( true ) );

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertInternalType(
			'bool',
			$instance->hasPermission( $permissionExaminer )
		);
	}

	public function testGetName() {

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertInternalType(
			'string',
			$instance->getName()
		);
	}

	public function testIsSeverityType() {

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertInternalType(
			'bool',
			$instance->isSeverityType( 'foo' )
		);
	}

	public function testGetIndicators() {

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertInternalType(
			'array',
			$instance->getIndicators()
		);
	}

	public function testGetModules() {

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertInternalType(
			'array',
			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$this->store
		);

		$this->assertInternalType(
			'string',
			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator_SameRevision() {

		$this->entityIdManager->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->will( $this->returnValue( 42 ) );

		$this->revisionGuard->expects( $this->once() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

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
			->will( $this->returnValue( 42 ) );

		$this->revisionGuard->expects( $this->once() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 1001 ) );

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
			->with( $this->equalTo( '_MDAT' ) )
			->will( $this->returnValue( 42 ) );

		$this->revisionGuard->expects( $this->once() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 1001 ) );

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
