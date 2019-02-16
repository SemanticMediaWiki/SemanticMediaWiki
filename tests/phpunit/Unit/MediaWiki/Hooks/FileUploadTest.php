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

		$this->testEnvironment = new TestEnvironment( [
			'smwgPageSpecialProperties' => [ '_MEDIA', '_MIME' ],
			'smwgNamespacesWithSemanticLinks' => [ NS_FILE => true ],
			'smwgMainCacheType'  => 'hash',
			'smwgEnableUpdateJobs' => false
		] );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			FileUpload::class,
			new FileUpload( $namespaceExaminer )
		);
	}

	public function testprocessEnabledNamespace() {

		$title = Title::newFromText( __METHOD__, NS_FILE );

		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceExaminer->expects( $this->atLeastOnce() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

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
			->setMethods( [ 'createFilePage' ] )
			->getMock();

		$pageCreator->expects( $this->once() )
			->method( 'createFilePage' )
			->with( $this->equalTo( $title ) )
			->will( $this->returnValue( $wikiFilePage ) );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$instance = new FileUpload(
			$namespaceExaminer
		);

		$reUploadStatus = true;

		$this->assertTrue(
			$instance->process( $file, $reUploadStatus )
		);

		$this->assertEquals(
			$wikiFilePage->smwFileReUploadStatus,
			$reUploadStatus
		);
	}

	public function testTryToProcessDisabledNamespace() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceExaminer->expects( $this->atLeastOnce() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( false ) );

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
			$namespaceExaminer
		);

		$instance->process( $file, false );
	}

}
