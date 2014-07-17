<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\FileUpload;
use SMW\Application;
use SMW\Settings;

use ParserOutput;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\FileUpload
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FileUploadTest extends \PHPUnit_Framework_TestCase {

	private $application;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();

		$settings = Settings::newFromArray( array(
			'smwgPageSpecialProperties' => array( '_MEDIA', '_MIME' ),
			'smwgCacheType'  => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$this->application->registerObject( 'Settings', $settings );
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$file = $this->getMockBuilder( 'File' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\FileUpload',
			new FileUpload( $file )
		);
	}

	public function testPerformUpdate() {

		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->application->registerObject( 'Store', $store );

		$file = $this->getMockBuilder( 'File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->will( $this->returnValue( new ParserOutput() ) );

		$this->application->registerObject( 'ContentParser', $contentParser );

		$instance = new FileUpload( $file, false );
		$this->assertTrue( $instance->process() );
	}

}
