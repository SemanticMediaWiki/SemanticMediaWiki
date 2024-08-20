<?php

namespace SMW\Tests\Integration;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Subobject;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;
use SMWDITime as DITime;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group Database
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GNU GPL v2+
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

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();
		$this->pageDeleter = $utilityFactory->newPageDeleter();

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
		$property = new DIProperty( 'SomePageProperty' );

		$this->subjects[] = $subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'SomePropertyPageValue', NS_MAIN, '' )
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
		$property = new DIProperty( '_MDAT' );

		$this->subjects[] = $subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new DITime( 1, '1970', '1', '1' )
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
		$property = new DIProperty( '_ASK' );

		$this->subjects[] = $subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$this->getStore()->updateData( $semanticData );

		$this->assertEmpty(
			$this->getStore()->getProperties( $subject )
		);
	}

	public function testAddUserDefinedBlobPropertyAsObjectToSemanticDataForStorage() {
		$property = new DIProperty( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$this->subjects[] = $subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIBlob( 'SomePropertyBlobValue' )
		);

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);
	}

	public function testAddUserDefinedPropertyAsDataValueToSemanticDataForStorage() {
		$propertyAsString = 'SomePropertyAsString';

		$this->subjects[] = $subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
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
		$this->subjects[] = $subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
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
				new DIProperty( 'Foo' ),
				new DIProperty( '_SKEY' )
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

		$pageOne = parent::getNonexistingTestPage( Title::newFromText( 'Foo-B' ) );
		parent::editPage( $pageOne, '#REDIRECT [[Foo-A]]' );

		$subject = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-A' ) );

		$pageTwo = parent::getNonexistingTestPage( $subject->getTitle() );
		parent::editPage( $pageTwo, '[[HasNoDisplayRedirectInconsistencyFor::Foo-B]]' );

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
			Title::newFromText( 'Foo-B' )
		];
	}

	public function testFetchSemanticDataForPreExistingDoubleRedirect() {
		$this->applicationFactory->clear();

		$pageB = parent::getNonexistingTestPage( Title::newFromText( 'Foo-B' ) );
		parent::editPage( $pageB, '#REDIRECT [[Foo-C]]' );
		$pageB = parent::getNonexistingTestPage( Title::newFromText( 'Foo-C' ) );

		$subject = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-A' ) );

		$pageSub = parent::getExistingTestPage( $subject->getTitle() );
		parent::editPage( $pageSub, '[[HasNoDisplayRedirectInconsistencyFor::Foo-B]]' );

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
			Title::newFromText( 'Foo-B' ),
			Title::newFromText( 'Foo-C' )
		];
	}

	public function testPrepareToFetchCorrectSemanticDataFromInternalCache() {
		$this->applicationFactory->clear();

		$redirect = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-A' ) );
		$pageA = parent::getExistingTestPage( $redirect->getTitle() );

		$target = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-C' ) );

		$pageC = parent::getNonexistingTestPage( $target->getTitle() );
		parent::editPage( $pageC, '{{#subobject:test|HasSomePageProperty=Foo-A}}' );

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
		$redirect = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-A' ) );
		$target = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-C' ) );

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
