<?php

namespace SMW\Tests\Protection;

use SMW\DataItemFactory;
use SMW\Protection\EditProtectionUpdater;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Protection\EditProtectionUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.5
 *
 * @author mwjames
 */
class EditProtectionUpdaterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;
	private $wikiPage;
	private $user;
	private $spyLogger;

	protected function setUp(): void {
		parent::setUp();

		$testEnvironment = new TestEnvironment();

		$this->spyLogger = $testEnvironment->getUtilityFactory()->newSpyLogger();
		$this->dataItemFactory = new DataItemFactory();

		$this->wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EditProtectionUpdater::class,
			new EditProtectionUpdater( $this->wikiPage, $this->user )
		);
	}

	public function testDoUpdateFromWithNoRestrictionsNoEditProtection() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN );

		$this->wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $subject->getTitle() );

		$this->wikiPage->expects( $this->never() )
			->method( 'doUpdateRestrictions' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$instance = new EditProtectionUpdater(
			$this->wikiPage,
			$this->user
		);

		$instance->setEditProtectionRight( 'Foo' );
		$instance->doUpdateFrom( $semanticData );

		$this->assertFalse(
			$instance->isRestrictedUpdate()
		);
	}

	public function testDoUpdateFromWithNoRestrictionsAnActiveEditProtection() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN );

		$this->wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $subject->getTitle() );

		$this->wikiPage->expects( $this->once() )
			->method( 'doUpdateRestrictions' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$instance = new EditProtectionUpdater(
			$this->wikiPage,
			$this->user
		);

		$instance->setEditProtectionRight( 'Foo' );

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->doUpdateFrom( $semanticData );

		$this->assertFalse(
			$instance->isRestrictedUpdate()
		);

		$this->assertContains(
			'add protection on edit, move',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testDoUpdateFromWithRestrictionsButNoTrueEditProtection() {
		$this->markTestSkipped( 'SUT needs refactoring' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getRestrictions' )
			->willReturn( [ 'Foo' ] );

		$this->wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->wikiPage->expects( $this->once() )
			->method( 'doUpdateRestrictions' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( false ) ] );

		$instance = new EditProtectionUpdater(
			$this->wikiPage,
			$this->user
		);

		$instance->setEditProtectionRight( 'Foo' );

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->doUpdateFrom( $semanticData );

		$this->assertFalse(
			$instance->isRestrictedUpdate()
		);

		$this->assertContains(
			'remove protection on edit, move',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testDoUpdateFromWithRestrictionsAnActiveEditProtection() {
		$this->markTestSkipped( 'SUT needs refactoring' );

		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getRestrictions' )
			->willReturn( [ 'Foo' ] );

		$this->wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->wikiPage->expects( $this->never() )
			->method( 'doUpdateRestrictions' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with( $property )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$instance = new EditProtectionUpdater(
			$this->wikiPage,
			$this->user
		);

		$instance->setEditProtectionRight( 'Foo' );

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->doUpdateFrom( $semanticData );

		$this->assertFalse(
			$instance->isRestrictedUpdate()
		);

		$this->assertContains(
			'Status already set, no update required',
			$this->spyLogger->getMessagesAsString()
		);
	}

}
