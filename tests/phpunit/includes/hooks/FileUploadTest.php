<?php

namespace SMW\Test;

use SMW\FileUpload;
use SMW\ExtensionContext;
use SMW\Settings;

use ParserOutput;
use Title;

/**
 * @covers \SMW\FileUpload
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FileUploadTest extends SemanticMediaWikiTestCase {

	public function getClass() {
		return '\SMW\FileUpload';
	}

	/**
	 * @return FileUpload
	 */
	private function newInstance( $file = null, $reupload = false, $settings = array() ) {

		if ( $file === null ) {
			$file = $this->newMockBuilder()->newObject( 'File' );
		}

		$contentParser = $this->newMockBuilder()->newObject( 'ContentParser', array(
			'getOutput'   => new ParserOutput(),
			'getRevision' => null
		) );

		$store = $this->newMockBuilder()->newObject( 'Store' );

		$context = new ExtensionContext();
		$container = $context->getDependencyBuilder()->getContainer();

		$container->registerObject( 'Settings', Settings::newFromArray( $settings ) );
		$container->registerObject( 'Store', $store );
		$container->registerObject( 'ContentParser', $contentParser );

		$instance = new FileUpload( $file, $reupload );
		$instance->invokeContext( $context );

		return $instance;
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testPerformUpdate() {

		$file = $this->newMockBuilder()->newObject( 'File', array(
			'getTitle' => Title::newFromText( __METHOD__ )
		) );

		$settings = array(
			'smwgCacheType'  => 'hash',
			'smwgPageSpecialProperties' => array( '_MEDIA', '_MIME' ),
			'smwgEnableUpdateJobs' => false
		);

		$instance = $this->newInstance( $file, false, $settings );

		$this->assertTrue(
			$instance->process(),
			'Asserts that process() always returns true'
		);

	}

}
