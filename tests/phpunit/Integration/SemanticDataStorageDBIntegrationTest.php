<?php

namespace SMW\Tests\Integration;

use MediaWiki\MediaWikiServices;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataModel\Subobject;
use SMW\DataValueFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class SemanticDataStorageDBIntegrationTest extends SMWIntegrationTestCase {

	private $applicationFactory;
	private $mwHooksHandler;

	private $semanticDataValidator;
	private $subjects = [];

	private $pageDeleter;
	private $pageCreator;

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();
		$this->pageDeleter = $utilityFactory->newPageDeleter();
		$this->pageCreator = $utilityFactory->newPageCreator();

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown(): void {
		$this->pageDeleter
			->doDeletePoolOfPages( $this->subjects );

		$this->applicationFactory->clear();
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testUserDefined_PageProperty_ToSemanticDataForStorage() {
		$property = new Property( 'SomePageProperty' );

		$this->subjects[] = $subject = WikiPage::newFromTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new WikiPage( 'SomePropertyPageValue', NS_MAIN, '' )
		);

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);

		foreach ( $this->getStore()->getProperties( $subject ) as $prop ) {
			$this->assertTrue( $prop->equals( $property ) );
		}
	}

	public function testFixedProperty_MDAT_ToSemanticDataForStorage() {
		$property = new Property( '_MDAT' );

		$this->subjects[] = $subject = WikiPage::newFromTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new Time( 1, '1970', '1', '1' )
		);

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);

		foreach ( $this->getStore()->getProperties( $subject ) as $prop ) {
			$this->assertTrue( $prop->equals( $property ) );
		}
	}

	public function testFixedProperty_ASK_NotForStorage() {
		$property = new Property( '_ASK' );

		$this->subjects[] = $subject = WikiPage::newFromTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$this->getStore()->updateData( $semanticData );

		$this->assertEmpty(
			$this->getStore()->getProperties( $subject )
		);
	}

	public function testAddUserDefinedBlobPropertyAsObjectToSemanticDataForStorage() {
		$property = new Property( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$this->subjects[] = $subject = WikiPage::newFromTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new Blob( 'SomePropertyBlobValue' )
		);

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);
	}

	public function testAddUserDefinedPropertyAsDataValueToSemanticDataForStorage() {
		$propertyAsString = 'SomePropertyAsString';

		$this->subjects[] = $subject = WikiPage::newFromTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$dataValue = DataValueFactory::getInstance()->newDataValueByText(
			$propertyAsString,
			'Foo',
			false,
			$subject
		);

		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$propertyAsString,
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);
	}

	public function testAddSubobjectToSemanticDataForStorage() {
		$this->subjects[] = $subject = WikiPage::newFromTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$subobject = new Subobject( $subject->getTitle() );
		$subobject->setEmptyContainerForId( 'SomeSubobject' );

		$subobject->getSemanticData()->addDataValue(
			DataValueFactory::getInstance()->newDataValueByText( 'Foo', 'Bar' )
		);

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->getStore()->updateData( $semanticData );

		$expected = [
			'propertyCount'  => 2,
			'properties' => [
				new Property( 'Foo' ),
				new Property( '_SKEY' )
			],
			'propertyValues' => [ 'Bar', __METHOD__ . '#SomeSubobject' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )->findSubSemanticData( 'SomeSubobject' )
		);
	}

	public function testFetchSemanticDataForPreExistingSimpleRedirect() {
		$this->applicationFactory->clear();

		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$this->pageCreator
			->createPage( $titleFactory->newFromText( 'Foo-B' ) )
			->doEdit( '#REDIRECT [[Foo-A]]' );

		$subject = WikiPage::newFromTitle( $titleFactory->newFromText( 'Foo-A' ) );

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit( '[[HasNoDisplayRedirectInconsistencyFor::Foo-B]]' );

		$expected = [
			'propertyCount' => 3,
			'propertyKeys'  => [ '_SKEY', '_MDAT', 'HasNoDisplayRedirectInconsistencyFor' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);

		$this->subjects = [
			$subject,
			$titleFactory->newFromText( 'Foo-B' )
		];
	}

	public function testFetchSemanticDataForPreExistingDoubleRedirect() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$this->pageCreator
			->createPage( $titleFactory->newFromText( 'Foo-B' ) )
			->doEdit( '#REDIRECT [[Foo-C]]' );

		$this->pageCreator
			->createPage( $titleFactory->newFromText( 'Foo-C' ) )
			->doEdit( '#REDIRECT [[Foo-A]]' );

		$subject = WikiPage::newFromTitle( $titleFactory->newFromText( 'Foo-A' ) );

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit( '[[HasNoDisplayRedirectInconsistencyFor::Foo-B]]' );

		$this->pageCreator
			->createPage( $titleFactory->newFromText( 'Foo-C' ) )
			->doEdit( '[[Has page::{{PAGENAME}}' );

		$expected = [
			'propertyCount' => 3,
			'propertyKeys'  => [ '_SKEY', '_MDAT', 'HasNoDisplayRedirectInconsistencyFor' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);

		$this->subjects = [
			$subject,
			$titleFactory->newFromText( 'Foo-B' ),
			$titleFactory->newFromText( 'Foo-C' )
		];
	}

	/**
	 * Issue 622/619
	 */
	public function testPrepareToFetchCorrectSemanticDataFromInternalCache() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$redirect = WikiPage::newFromTitle( $titleFactory->newFromText( 'Foo-A' ) );

		$this->pageCreator
			->createPage( $redirect->getTitle() )
			->doEdit( '#REDIRECT [[Foo-C]]' );

		$target = WikiPage::newFromTitle( $titleFactory->newFromText( 'Foo-C' ) );

		$this->pageCreator
			->createPage( $target->getTitle() )
			->doEdit( '{{#subobject:test|HasSomePageProperty=Foo-A}}' );

		$this->assertEmpty(
			$this->getStore()->getSemanticData( $redirect )->findSubSemanticData( 'test' )
		);

		$this->assertNotEmpty(
			$this->getStore()->getSemanticData( $target )->findSubSemanticData( 'test' )
		);
	}

	/**
	 * @depends testPrepareToFetchCorrectSemanticDataFromInternalCache
	 */
	public function testVerifyToFetchCorrectSemanticDataFromInternalCache() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$redirect = WikiPage::newFromTitle( $titleFactory->newFromText( 'Foo-A' ) );
		$target = WikiPage::newFromTitle( $titleFactory->newFromText( 'Foo-C' ) );

		$this->assertEmpty(
			$this->getStore()->getSemanticData( $redirect )->findSubSemanticData( 'test' )
		);

		$this->assertNotEmpty(
			$this->getStore()->getSemanticData( $target )->findSubSemanticData( 'test' )
		);

		$this->subjects = [
			$redirect,
			$target
		];
	}

}
