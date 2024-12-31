<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\HookContainer\HookContainer;
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
class FileUploadTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $propertySpecificationLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgPageSpecialProperties' => [ '_MEDIA', '_MIME' ],
			'smwgNamespacesWithSemanticLinks' => [ NS_FILE => true ],
			'smwgMainCacheType'  => 'hash',
			'smwgEnableUpdateJobs' => false
		] );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();
		$hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			FileUpload::class,
			new FileUpload( $namespaceExaminer, $hookContainer )
		);
	}

	public function testprocessEnabledNamespace() {
		$title = Title::newFromText( __METHOD__, NS_FILE );

		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceExaminer->expects( $this->atLeastOnce() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$wikiFilePage = $this->getMockBuilder( '\WikiFilePage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiFilePage->expects( $this->once() )
			->method( 'getParserOutput' )
			->willReturn( new ParserOutput() );

		$wikiFilePage->expects( $this->atLeastOnce() )
			->method( 'getFile' )
			->willReturn( $file );

		$pageCreator = $this->getMockBuilder( 'SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'createFilePage' ] )
			->getMock();

		$pageCreator->expects( $this->once() )
			->method( 'createFilePage' )
			->with( $title )
			->willReturn( $wikiFilePage );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$instance = new FileUpload(
			$namespaceExaminer,
			$this->getMockBuilder( HookContainer::class )
				->disableOriginalConstructor()
				->getMock()
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
			->willReturn( false );

		$file = $this->getMockBuilder( 'File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$pageCreator = $this->getMockBuilder( 'SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->never() ) // <-- never
			->method( 'createFilePage' );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$instance = new FileUpload(
			$namespaceExaminer,
			$this->getMockBuilder( HookContainer::class )
				->disableOriginalConstructor()
				->getMock()
		);

		$instance->process( $file, false );
	}

}
