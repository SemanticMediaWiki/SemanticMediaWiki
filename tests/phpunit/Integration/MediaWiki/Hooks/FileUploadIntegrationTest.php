<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\MwDBaseUnitTestCase;

use SMW\DIWikiPage;
use SMW\ApplicationFactory;
use SMW\Localizer;

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
 * @since   2.1
 *
 * @author mwjames
 */
class FileUploadIntegrationTest extends MwDBaseUnitTestCase {

	private $mwHooksHandler;
	private $fixturesFileProvider;
	private $semanticDataValidator;
	private $pageEditor;

	/**
	 * MW GLOBALS to be restored after the test
	 */
	private $wgFileExtensions;
	private $wgEnableUploads;
	private $wgVerifyMimeType;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->fixturesFileProvider = $utilityFactory->newFixturesFactory()->newFixturesFileProvider();
		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();
		$this->pageEditor = $utilityFactory->newPageEditor();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = array(
			'smwgPageSpecialProperties' => array( '_MEDIA', '_MIME' ),
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true, NS_FILE => true ),
			'smwgCacheType' => 'hash',
		);

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

	//	$this->getStore()->clear();
	//	$this->getStore()->setupStore( false );

		$this->wgEnableUploads  = $GLOBALS['wgEnableUploads'];
		$this->wgFileExtensions = $GLOBALS['wgFileExtensions'];
		$this->wgVerifyMimeType = $GLOBALS['wgVerifyMimeType'];

		$this->mwHooksHandler->register(
			'FileUpload',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'FileUpload' )
		);

		$this->mwHooksHandler->register(
			'InternalParseBeforeLinks',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'InternalParseBeforeLinks' )
		);

		$this->mwHooksHandler->register(
			'LinksUpdateConstructed',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'LinksUpdateConstructed' )
		);

		$GLOBALS['wgEnableUploads'] = true;
		$GLOBALS['wgFileExtensions'] = array( 'txt' );
		$GLOBALS['wgVerifyMimeType'] = true;
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();

		$GLOBALS['wgEnableUploads'] = $this->wgEnableUploads;
		$GLOBALS['wgFileExtensions'] = $this->wgFileExtensions;
		$GLOBALS['wgVerifyMimeType'] = $this->wgVerifyMimeType;

		parent::tearDown();
	}

	public function testFileUploadForDummyTextFile() {

		$subject = new DIWikiPage( 'Foo.txt', NS_FILE );
		$fileNS = Localizer::getInstance()->getNamespaceTextById( NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile( 'Foo.txt' );

		$this->assertTrue(
			$dummyTextFile->doUpload( '[[HasFile::File:Foo.txt]]' )
		);

		$expected = array(
			'propertyCount'  => 4,
			'propertyKeys'   => array( 'HasFile', '_MEDIA', '_MIME', '_SKEY' ),
			'propertyValues' => array( "$fileNS:Foo.txt", 'TEXT', 'text/plain', 'Foo.txt' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

	/**
	 * @depends testFileUploadForDummyTextFile
	 */
	public function testReUploadDummyTextFileToEditFilePage() {

		$subject = new DIWikiPage( 'Foo.txt', NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile( 'Foo.txt' );
		$dummyTextFile->doUpload();

		$this->pageEditor
			->editPage( $subject->getTitle() )
			->doEdit( '[[Ichi::Maru|Kyū]]' );

		// File page content is kept from the initial upload
		$expected = array(
			'propertyCount'  => 4,
			'propertyKeys'   => array( '_MEDIA', '_MIME', '_SKEY', 'Ichi' ),
			'propertyValues' => array( 'TEXT', 'text/plain', 'Foo.txt', 'Maru' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

	public function testDummyTextFileUploadForDisabledNamespace() {

		$this->applicationFactory->getSettings()->set(
			'smwgNamespacesWithSemanticLinks', array( NS_FILE => false )
		);

		$subject = new DIWikiPage( 'Bar.txt', NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile( 'Bar.txt' );

		$this->assertTrue(
			$dummyTextFile->doUpload( '[[HasFile::File:Bar.txt]]' )
		);

		$expected = array(
			'propertyCount'  => 1,
			'propertyKeys'   => array( '_SKEY' ),
			'propertyValues' => array( 'Bar.txt' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

}
