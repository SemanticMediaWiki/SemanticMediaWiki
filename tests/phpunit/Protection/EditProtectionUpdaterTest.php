<?php

namespace SMW\Tests\Protection;

use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Protection\EditProtectionUpdater;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Protection\EditProtectionUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since  2.5
 *
 * @author mwjames
 */
class EditProtectionUpdaterTest extends TestCase {

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

		$this->user = $this->getMockBuilder( User::class )
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

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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

		$this->assertStringContainsString(
			'add protection on edit, move',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testDoUpdateFromWithRestrictionsButNoTrueEditProtection() {
		$this->markTestSkipped( 'SUT needs refactoring' );

		$title = $this->getMockBuilder( Title::class )
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

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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

		$this->assertStringContainsString(
			'remove protection on edit, move',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testDoUpdateFromWithRestrictionsAnActiveEditProtection() {
		$this->markTestSkipped( 'SUT needs refactoring' );

		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		$title = $this->getMockBuilder( Title::class )
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

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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

		$this->assertStringContainsString(
			'Status already set, no update required',
			$this->spyLogger->getMessagesAsString()
		);
	}

}
