<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Hooks\FileUpload;
use SMW\MediaWiki\Jobs\ParserDataFactory;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageInfoProvider;
use SMW\NamespaceExaminer;
use SMW\ParserData;
use SMW\Property\AnnotatorFactory;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\PredefinedPropertyAnnotator;
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
			new FileUpload(
				$namespaceExaminer,
				$hookContainer,
				$pageCreator,
				$this->createMock( ParserDataFactory::class ),
				$this->createMock( MwCollaboratorFactory::class ),
				$this->createMock( AnnotatorFactory::class )
			)
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

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->setMethods( [ 'createFilePage' ] )
			->getMock();

		$pageCreator->expects( $this->once() )
			->method( 'createFilePage' )
			->with( $title )
			->willReturn( $wikiFilePage );

		$semanticData = $this->createMock( SemanticData::class );

		$parserData = $this->createMock( ParserData::class );
		$parserData->method( 'getSemanticData' )->willReturn( $semanticData );

		$parserDataFactory = $this->createMock( ParserDataFactory::class );
		$parserDataFactory->method( 'newParserData' )->willReturn( $parserData );

		$mwCollaboratorFactory = $this->createMock( MwCollaboratorFactory::class );
		$mwCollaboratorFactory->method( 'newPageInfoProvider' )
			->willReturn( $this->createMock( PageInfoProvider::class ) );

		$nullPropertyAnnotator = $this->createMock( NullPropertyAnnotator::class );
		$predefinedPropertyAnnotator = $this->createMock( PredefinedPropertyAnnotator::class );

		$propertyAnnotatorFactory = $this->createMock( AnnotatorFactory::class );
		$propertyAnnotatorFactory->method( 'newNullPropertyAnnotator' )
			->willReturn( $nullPropertyAnnotator );
		$propertyAnnotatorFactory->method( 'newPredefinedPropertyAnnotator' )
			->willReturn( $predefinedPropertyAnnotator );

		$instance = new FileUpload(
			$namespaceExaminer,
			$this->getMockBuilder( HookContainer::class )
				->disableOriginalConstructor()
				->getMock(),
			$pageCreator,
			$parserDataFactory,
			$mwCollaboratorFactory,
			$propertyAnnotatorFactory
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
			$pageCreator,
			$this->createMock( ParserDataFactory::class ),
			$this->createMock( MwCollaboratorFactory::class ),
			$this->createMock( AnnotatorFactory::class )
		);

		$instance->onFileUpload( $file, false, false );
	}

}
