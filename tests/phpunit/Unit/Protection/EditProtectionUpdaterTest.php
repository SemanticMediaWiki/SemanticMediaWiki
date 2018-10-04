<?php

namespace SMW\Tests\Protection;

use SMW\DataItemFactory;
use SMW\Protection\EditProtectionUpdater;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Protection\EditProtectionUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.5
 *
 * @author mwjames
 */
class EditProtectionUpdaterTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $wikiPage;
	private $user;
	private $spyLogger;

	protected function setUp() {
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
			->will( $this->returnValue( $subject->getTitle() ) );

		$this->wikiPage->expects( $this->never() )
			->method( 'doUpdateRestrictions' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

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
			->will( $this->returnValue( $subject->getTitle() ) );

		$this->wikiPage->expects( $this->once() )
			->method( 'doUpdateRestrictions' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBoolean( true ) ] ) );

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

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'isProtected' )
			->with( $this->equalTo( 'edit' ) )
			->will( $this->returnValue( true ) );

		$title->expects( $this->once() )
			->method( 'getRestrictions' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		$this->wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->wikiPage->expects( $this->once() )
			->method( 'doUpdateRestrictions' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBoolean( false ) ] ) );

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

		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'isProtected' )
			->with( $this->equalTo( 'edit' ) )
			->will( $this->returnValue( true ) );

		$title->expects( $this->once() )
			->method( 'getRestrictions' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		$this->wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->wikiPage->expects( $this->never() )
			->method( 'doUpdateRestrictions' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with( $this->equalTo( $property ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBoolean( true ) ] ) );

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
