<?php

namespace SMW\Tests\MediaWiki\Hooks;

use ParserOutput;
use SMW\MediaWiki\Hooks\FileUpload;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\FileUpload
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FileUploadTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( array(
			'smwgPageSpecialProperties' => array( '_MEDIA', '_MIME' ),
			'smwgNamespacesWithSemanticLinks' => array( NS_FILE => true ),
			'smwgCacheType'  => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'hasIDFor' ) )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
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

	public function testPerformUpdateForEnabledNamespace() {

		$title = Title::newFromText( __METHOD__, NS_FILE );

		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$wikiFilePage = $this->getMockBuilder( '\WikiFilePage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiFilePage->expects( $this->once() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( new ParserOutput() ) );

		$wikiFilePage->expects( $this->atLeastOnce() )
			->method( 'getFile' )
			->will( $this->returnValue( $file ) );

		$pageCreator = $this->getMockBuilder( 'SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->setMethods( array( 'createFilePage' ) )
			->getMock();

		$pageCreator->expects( $this->once() )
			->method( 'createFilePage' )
			->with( $this->equalTo( $title ) )
			->will( $this->returnValue( $wikiFilePage ) );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$instance = new FileUpload(
			$file,
			true
		);

		$this->assertTrue(
			$instance->process()
		);

		$this->assertTrue(
			$wikiFilePage->smwFileReUploadStatus
		);
	}

	public function testTryToPerformUpdateForDisabledNamespace() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$file = $this->getMockBuilder( 'File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$pageCreator = $this->getMockBuilder( 'SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->never() ) // <-- never
			->method( 'createFilePage' );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$instance = new FileUpload(
			$file,
			false
		);

		$this->assertTrue(
			$instance->process()
		);
	}

}
