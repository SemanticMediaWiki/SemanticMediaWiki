<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\FileUpload;
use SMW\MediaWiki\PageCreator;
use SMW\NamespaceExaminer;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\FileUpload
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class FileUploadTest extends TestCase {

	private TestEnvironment $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgPageSpecialProperties' => [ '_MEDIA', '_MIME' ],
			'smwgNamespacesWithSemanticLinks' => [ NS_FILE => true ],
			'smwgMainCacheType'  => 'hash',
			'smwgEnableUpdateJobs' => false
		] );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();
		$hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();
		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			FileUpload::class,
			new FileUpload( $namespaceExaminer, $hookContainer, $pageCreator )
		);
	}

	public function testprocessEnabledNamespace() {
		$title = Title::newFromText( __METHOD__, NS_FILE );

		$namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
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

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->setMethods( [ 'createFilePage' ] )
			->getMock();

		$pageCreator->expects( $this->once() )
			->method( 'createFilePage' )
			->with( $title )
			->willReturn( $wikiFilePage );

		$instance = new FileUpload(
			$namespaceExaminer,
			$this->getMockBuilder( HookContainer::class )
				->disableOriginalConstructor()
				->getMock(),
			$pageCreator
		);

		$reUploadStatus = true;

		$this->assertTrue(
			$instance->onFileUpload( $file, $reUploadStatus, false )
		);
	}

	public function testTryToProcessDisabledNamespace() {
		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
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

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->never() ) // <-- never
			->method( 'createFilePage' );

		$instance = new FileUpload(
			$namespaceExaminer,
			$this->getMockBuilder( HookContainer::class )
				->disableOriginalConstructor()
				->getMock(),
			$pageCreator
		);

		$instance->onFileUpload( $file, false, false );
	}

}
