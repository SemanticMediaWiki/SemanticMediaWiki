<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\DataValueFactory;
use SMW\Subobject;

use SMWDIBlob as DIBlob;

use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SemanticDataStorageDBIntegrationTest extends MwDBaseUnitTestCase {

	private $applicationFactory;
	private $mwHooksHandler;

	private $semanticDataValidator;
	private $subjects = array();

	private $pageDeleter;
	private $pageCreator;

	protected function setUp() {
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

	protected function tearDown() {

		$this->pageDeleter
			->doDeletePoolOfPages( $this->subjects );

		$this->applicationFactory->clear();
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testAddUserDefinedPagePropertyAsObjectToSemanticDataForStorage() {

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

		$dataValue = DataValueFactory::getInstance()->newPropertyValue(
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
			DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' )
		);

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->getStore()->updateData( $semanticData );

		$expected = array(
			'propertyCount'  => 2,
			'properties' => array(
				new DIProperty( 'Foo' ),
				new DIProperty( '_SKEY' )
			),
			'propertyValues' => array( 'Bar', __METHOD__ )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )->findSubSemanticData( 'SomeSubobject' )
		);
	}

	public function testFetchSemanticDataForPreExistingSimpleRedirect() {

		$this->applicationFactory->clear();

		$this->pageCreator
			->createPage( Title::newFromText( 'Foo-B' ) )
			->doEdit( '#REDIRECT [[Foo-A]]' );

		$subject = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-A' ) );

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit( '[[HasNoDisplayRedirectInconsistencyFor::Foo-B]]' );

		$expected = array(
			'propertyCount' => 3,
			'propertyKeys'  => array( '_SKEY', '_MDAT', 'HasNoDisplayRedirectInconsistencyFor' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);

		$this->subjects = array(
			$subject,
			Title::newFromText( 'Foo-B' )
		);
	}

	public function testFetchSemanticDataForPreExistingDoubleRedirect() {

		$this->pageCreator
			->createPage( Title::newFromText( 'Foo-B' ) )
			->doEdit( '#REDIRECT [[Foo-C]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Foo-C' ) )
			->doEdit( '#REDIRECT [[Foo-A]]' );

		$subject = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-A' ) );

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit( '[[HasNoDisplayRedirectInconsistencyFor::Foo-B]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Foo-C' ) )
			->doEdit( '[[Has page::{{PAGENAME}}' );

		$expected = array(
			'propertyCount' => 3,
			'propertyKeys'  => array( '_SKEY', '_MDAT', 'HasNoDisplayRedirectInconsistencyFor' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);

		$this->subjects = array(
			$subject,
			Title::newFromText( 'Foo-B' ),
			Title::newFromText( 'Foo-C' )
		);
	}

	/**
	 * Issue 622/619
	 */
	public function testPrepareToFetchCorrectSemanticDataFromInternalCache() {

		$redirect = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-A' ) );

		$this->pageCreator
			->createPage( $redirect->getTitle() )
			->doEdit( '#REDIRECT [[Foo-C]]' );

		$target = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-C' ) );

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

		$redirect = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-A' ) );
		$target = DIWikiPage::newFromTitle( Title::newFromText( 'Foo-C' ) );

		$this->assertEmpty(
			$this->getStore()->getSemanticData( $redirect )->findSubSemanticData( 'test' )
		);

		$this->assertNotEmpty(
			$this->getStore()->getSemanticData( $target )->findSubSemanticData( 'test' )
		);

		$this->subjects = array(
			$redirect,
			$target
		);
	}

}
