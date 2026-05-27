<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use SMW\DataItems\WikiPage;
use SMW\Localizer\Localizer;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\SMWDeclarativeHookReseater;

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
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class FileUploadIntegrationTest extends SMWIntegrationTestCase {

	private $fixturesFileProvider;
	private $semanticDataValidator;
	private $pageEditor;

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->fixturesFileProvider = $utilityFactory->newFixturesFactory()->newFixturesFileProvider();
		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();
		$this->pageEditor = $utilityFactory->newPageEditor();

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

		$reseater = new SMWDeclarativeHookReseater(
			MediaWikiServices::getInstance()->getHookContainer()
		);
		foreach ( [ 'FileUpload', 'InternalParseBeforeLinks', 'LinksUpdateComplete' ] as $hook ) {
			$this->clearHook( $hook );
			$this->setTemporaryHook( $hook, $reseater->buildSmwHandlerFor( $hook ) );
		}

		$this->getStore()->setup( false );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();

		parent::tearDown();
	}

	public function testFileUploadForDummyTextFile() {
		Localizer::getInstance()->clear();

		$subject = new WikiPage( 'Foo.txt', NS_FILE );
		$fileNS = Localizer::getInstance()->getNsText( NS_FILE );

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
		$subject = new WikiPage( 'Foo.txt', NS_FILE );

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
