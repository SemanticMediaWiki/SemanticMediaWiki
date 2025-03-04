<?php

namespace SMW\Tests\Parser;

use SMW\Parser\RecursiveTextProcessor;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Parser\RecursiveTextProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RecursiveTextProcessorTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $parser;
	private $parserOptions;
	private $parserOutput;
	private $title;

	protected function setUp(): void {
		parent::setUp();

		$this->parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$this->parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->parserOutput->expects( $this->any() )
			->method( 'getHeadItems' )
			->willReturn( [] );

		$this->title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RecursiveTextProcessor::class,
			new RecursiveTextProcessor( $this->parser )
		);
	}

	public function testRecursivePreprocess_NO_RecursiveAnnotation() {
		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->willReturn( $this->parserOptions );

		$this->parser->expects( $this->once() )
			->method( 'replaceVariables' )
			->with( 'Foo' )
			->willReturnArgument( 0 );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$this->assertSame(
			'[[SMW::off]]Foo[[SMW::on]]',
			$instance->recursivePreprocess( 'Foo' )
		);
	}

	public function testRecursivePreprocess_NO_RecursiveAnnotationWithAnnotationBlockToRemoveCategories() {
		$this->parserOutput->expects( $this->atLeastOnce() )
			->method( 'getExtensionData' )
			->with(	\SMW\ParserData::ANNOTATION_BLOCK )
			->willReturn( [ '123' => true ] );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $this->parserOutput );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->willReturn( $this->parserOptions );

		$this->parser->expects( $this->once() )
			->method( 'replaceVariables' )
			->with( 'Foo [[Category:Bar]][[Category:Test: Abc]]' )
			->willReturnArgument( 0 );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->uniqid( '123' );

		$this->assertSame(
			'[[SMW::off]]Foo [[SMW::on]]',
			$instance->recursivePreprocess( 'Foo [[Category:Bar]][[Category:Test: Abc]]' )
		);
	}

	public function testRecursivePreprocess_NO_RecursiveAnnotationWithNoAnnotationBlockToRetainCategories() {
		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $this->parserOutput );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->willReturn( $this->parserOptions );

		$this->parser->expects( $this->once() )
			->method( 'replaceVariables' )
			->with( 'Foo [[Category:Bar]][[Category:Test: Abc]]' )
			->willReturnArgument( 0 );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->uniqid( '123' );

		$this->assertSame(
			'[[SMW::off]]Foo [[Category:Bar]][[Category:Test: Abc]][[SMW::on]]',
			$instance->recursivePreprocess( 'Foo [[Category:Bar]][[Category:Test: Abc]]' )
		);
	}

	public function testRecursivePreprocess_WITH_RecursiveAnnotation() {
		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->willReturn( $this->parserOptions );

		$this->parser->expects( $this->once() )
			->method( 'recursivePreprocess' )
			->with( 'Foo' )
			->willReturnArgument( 0 );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->setRecursiveAnnotation( true );

		$this->assertSame(
			'Foo',
			$instance->recursivePreprocess( 'Foo' )
		);
	}

	public function testRecursivePreprocess_WITH_IncompleteParser() {
		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->setRecursiveAnnotation( false );

		$this->assertSame(
			'[[SMW::off]]Foo[[SMW::on]]',
			$instance->recursivePreprocess( 'Foo' )
		);

		$instance->setRecursiveAnnotation( true );

		$this->assertSame(
			'Foo',
			$instance->recursivePreprocess( 'Foo' )
		);
	}

	public function testRecursiveTagParse() {
		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->willReturn( $this->parserOptions );

		$this->parser->expects( $this->once() )
			->method( 'recursiveTagParse' )
			->with( 'Foo' )
			->willReturnArgument( 0 );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$this->assertSame(
			'Foo',
			$instance->recursiveTagParse( 'Foo' )
		);
	}

	public function testRecursiveTagParse_WITH_IncompleteParser() {
		$this->parser->expects( $this->once() )
			->method( 'parse' )
			->with( 'Foo__NOTOC__' )
			->willReturn( $this->parserOutput );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->recursiveTagParse( 'Foo' );
	}

	public function testExpandTemplate() {
		$this->parser->expects( $this->once() )
			->method( 'preprocess' )
			->with( '{{Foo}}' );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->expandTemplate( '{{Foo}}' );
	}

	public function testRecursivePreprocess_ExceededRecursion() {
		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->willReturn( $this->parserOptions );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->setMaxRecursionDepth( 0 );
		$instance->recursivePreprocess( 'Foo' );

		$this->assertNotEmpty(
			$instance->getError()
		);
	}

	public function testRecursiveTagParse_ExceededRecursion() {
		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->setMaxRecursionDepth( 0 );
		$instance->recursiveTagParse( 'Foo' );

		$this->assertNotEmpty(
			$instance->getError()
		);
	}

	public function testTranscludeAnnotationWithoutUniquidThrowsException() {
		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $this->parserOutput );

		if ( version_compare( MW_VERSION, '1.42', '>=' ) ) {
			$this->parser->expects( $this->atLeastOnce() )
				->method( 'getOptions' )
				->willReturn( $this->parserOptions );
		}

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$this->expectException( 'RuntimeException' );
		$instance->transcludeAnnotation( false );
	}

	public function testTranscludeAnnotation_FALSE() {
		$this->parserOutput->expects( $this->atLeastOnce() )
			->method( 'setExtensionData' )
			->with(
				\SMW\ParserData::ANNOTATION_BLOCK,
				[ '123' => true ] );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $this->parserOutput );

		if ( version_compare( MW_VERSION, '1.42', '>=' ) ) {
			$this->parser->expects( $this->atLeastOnce() )
				->method( 'getOptions' )
				->willReturn( $this->parserOptions );
		}

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->uniqid( '123' );
		$instance->transcludeAnnotation( false );
	}

	public function testReleaseAnnotationBlock() {
		$this->parserOutput->expects( $this->atLeastOnce() )
			->method( 'getExtensionData' )
			->with(	\SMW\ParserData::ANNOTATION_BLOCK )
			->willReturn( [ '123' => true ] );

		$this->parserOutput->expects( $this->atLeastOnce() )
			->method( 'setExtensionData' )
			->with(
				\SMW\ParserData::ANNOTATION_BLOCK,
				false );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( $this->parserOutput );

		if ( version_compare( MW_VERSION, '1.42', '>=' ) ) {
			$this->parser->expects( $this->atLeastOnce() )
				->method( 'getOptions' )
				->willReturn( $this->parserOptions );
		}

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->uniqid( '123' );
		$instance->releaseAnnotationBlock();
	}

}
