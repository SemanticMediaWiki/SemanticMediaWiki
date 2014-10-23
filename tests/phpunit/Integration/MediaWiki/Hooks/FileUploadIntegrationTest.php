<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\Tests\Util\UtilityFactory;
use SMW\Tests\MwDBaseUnitTestCase;

use SMW\DIWikiPage;
use SMW\Application;

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

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->application = Application::getInstance();

		$settings = array(
			'smwgPageSpecialProperties' => array( '_MEDIA', '_MIME' ),
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true, NS_FILE => true ),
			'smwgCacheType' => 'hash',
		);

		foreach ( $settings as $key => $value ) {
			$this->application->getSettings()->set( $key, $value );
		}

		$this->wgEnableUploads  = $GLOBALS['wgEnableUploads'];
		$this->wgFileExtensions = $GLOBALS['wgFileExtensions'];
		$this->wgVerifyMimeType = $GLOBALS['wgVerifyMimeType'];

		$this->mwHooksHandler->register(
			'FileUpload',
			$this->mwHooksHandler->getHookRegistry()->getDefinition( 'FileUpload' )
		);

		$this->mwHooksHandler->register(
			'InternalParseBeforeLinks',
			$this->mwHooksHandler->getHookRegistry()->getDefinition( 'InternalParseBeforeLinks' )
		);

		$this->mwHooksHandler->register(
			'LinksUpdateConstructed',
			$this->mwHooksHandler->getHookRegistry()->getDefinition( 'LinksUpdateConstructed' )
		);
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();

		$GLOBALS['wgEnableUploads'] = $this->wgEnableUploads;
		$GLOBALS['wgFileExtensions'] = $this->wgFileExtensions;
		$GLOBALS['wgVerifyMimeType'] = $this->wgVerifyMimeType;

		parent::tearDown();
	}

	public function testFileUploadForDummyTextFile() {

		$GLOBALS['wgEnableUploads'] = true;
		$GLOBALS['wgFileExtensions'] = array( 'txt' );
		$GLOBALS['wgVerifyMimeType'] = true;

		$subject = new DIWikiPage( 'Foo.txt', NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile( 'Foo.txt' );

		$this->assertTrue(
			$dummyTextFile->doUpload( '[[HasFile::File:Foo.txt]]' )
		);

		$expected = array(
			'propertyCount'  => 4,
			'propertyKeys'   => array( 'HasFile', '_MEDIA', '_MIME', '_SKEY' ),
			'propertyValues' => array( 'File:Foo.txt', 'TEXT', 'text/plain', 'Foo.txt' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

}
