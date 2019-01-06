<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Tests\MwDBaseUnitTestCase;
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

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->fixturesFileProvider = $utilityFactory->newFixturesFactory()->newFixturesFileProvider();
		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();
		$this->pageEditor = $utilityFactory->newPageEditor();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->testEnvironment->withConfiguration( [
			'smwgPageSpecialProperties' => [ '_MEDIA', '_MIME' ],
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true, NS_FILE => true ],
			'smwgMainCacheType' => 'hash',
		] );

		$this->testEnvironment->withConfiguration( [
			'wgEnableUploads' => true,
			'wgFileExtensions' => [ 'txt' ],
			'wgVerifyMimeType' => true
		] );

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

		$this->getStore()->setup( false );
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();
		$this->testEnvironment->tearDown();

		parent::tearDown();
	}

	public function testFileUploadForDummyTextFile() {
		Localizer::getInstance()->clear();

		$subject = new DIWikiPage( 'Foo.txt', NS_FILE );
		$fileNS = Localizer::getInstance()->getNamespaceTextById( NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile( 'Foo.txt' );

		$this->assertTrue(
			$dummyTextFile->doUpload( '[[HasFile::File:Foo.txt]]' )
		);

		$this->testEnvironment->executePendingDeferredUpdates();

		$expected = [
			'propertyCount'  => 4,
			'propertyKeys'   => [ 'HasFile', '_MEDIA', '_MIME', '_SKEY' ],
			'propertyValues' => [ "$fileNS:Foo.txt", 'TEXT', 'text/plain', 'Foo.txt' ]
		];

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

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->pageEditor
			->editPage( $subject->getTitle() )
			->doEdit( '[[Ichi::Maru|Kyū]]' );

		// File page content is kept from the initial upload
		$expected = [
			'propertyCount'  => 4,
			'propertyKeys'   => [ '_MEDIA', '_MIME', '_SKEY', 'Ichi' ],
			'propertyValues' => [ 'TEXT', 'text/plain', 'Foo.txt', 'Maru' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

}
