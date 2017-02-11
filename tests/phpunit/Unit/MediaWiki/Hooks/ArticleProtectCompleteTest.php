<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ArticleProtectComplete;
use SMW\PropertyAnnotators\EditProtectedPropertyAnnotator;
use SMW\Tests\TestEnvironment;
use SMW\DataItemFactory;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleProtectComplete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ArticleProtectCompleteTest extends \PHPUnit_Framework_TestCase {

	private $spyLogger;
	private $testEnvironment;
	private $semanticDataFactory;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->spyLogger = $this->testEnvironment->getUtilityFactory()->newSpyLogger();
		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$editInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\EditInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ArticleProtectComplete::class,
			new ArticleProtectComplete( $title, $editInfoProvider )
		);
	}

	public function testProcessOnSelfInvokedReason() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$editInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\EditInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ArticleProtectComplete(
			$title,
			$editInfoProvider
		);

		$instance->setLogger( $this->spyLogger );

		$protections = array();
		$reason = \SMW\Message::get( 'smw-edit-protection-auto-update' );

		$instance->process( $protections, $reason );

		$this->assertContains(
			'No changes required, invoked by own process',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testProcessOnMatchableEditProtectionToAddAnnotation() {

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_SPECIAL ) );

		$editInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\EditInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$editInfoProvider->expects( $this->once() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$instance = new ArticleProtectComplete(
			$title,
			$editInfoProvider
		);

		$instance->setLogger( $this->spyLogger );
		$instance->setEditProtectionRight( 'Foo' );

		$protections = array( 'edit' => 'Foo' );
		$reason = '';

		$instance->process( $protections, $reason );

		$this->assertContains(
			'ArticleProtectComplete addProperty `Is edit protected`',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testProcessOnUnmatchableEditProtectionToRemoveAnnotation() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_SPECIAL )
		);

		$dataItem = $this->dataItemFactory->newDIBoolean( true );
		$dataItem->setOption( EditProtectedPropertyAnnotator::SYSTEM_ANNOTATION, true );

		$semanticData->addPropertyObjectValue(
			$this->dataItemFactory->newDIProperty( '_EDIP' ),
			$dataItem
		);

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->will( $this->returnValue( $semanticData ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_SPECIAL ) );

		$editInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\EditInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$editInfoProvider->expects( $this->once() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$instance = new ArticleProtectComplete(
			$title,
			$editInfoProvider
		);

		$instance->setLogger( $this->spyLogger );
		$instance->setEditProtectionRight( 'Foo2' );

		$protections = array( 'edit' => 'Foo' );
		$reason = '';

		$instance->process( $protections, $reason );

		$this->assertContains(
			'ArticleProtectComplete removeProperty `Is edit protected`',
			$this->spyLogger->getMessagesAsString()
		);
	}

}
