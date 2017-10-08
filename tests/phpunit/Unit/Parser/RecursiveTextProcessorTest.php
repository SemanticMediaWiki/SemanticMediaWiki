<?php

namespace SMW\Tests\Parser;

use SMW\Parser\RecursiveTextProcessor;

/**
 * @covers \SMW\Parser\RecursiveTextProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RecursiveTextProcessorTest extends \PHPUnit_Framework_TestCase {

	private $parser;
	private $parserOptions;
	private $parserOutput;
	private $title;

	protected function setUp() {
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
			->will( $this->returnValue( [] ) );

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
			->will( $this->returnValue( $this->title ) );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->will( $this->returnValue( $this->parserOptions ) );

		$this->parser->expects( $this->once() )
			->method( 'replaceVariables' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnArgument( 0 ) );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$this->assertSame(
			'[[SMW::off]]Foo[[SMW::on]]',
			$instance->recursivePreprocess( 'Foo' )
		);
	}

	public function testRecursivePreprocess_WITH_RecursiveAnnotation() {

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->will( $this->returnValue( $this->parserOptions ) );

		$this->parser->expects( $this->once() )
			->method( 'recursivePreprocess' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnArgument( 0 ) );

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
			->will( $this->returnValue( $this->title ) );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->will( $this->returnValue( $this->parserOptions ) );

		$this->parser->expects( $this->once() )
			->method( 'recursiveTagParse' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnArgument( 0 ) );

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
			->with( $this->equalTo( 'Foo__NOTOC__' ) )
			->will( $this->returnValue( $this->parserOutput ) );

		$instance = new RecursiveTextProcessor(
			$this->parser
		);

		$instance->recursiveTagParse( 'Foo' );
	}

	public function testRecursivePreprocess_ExceededRecursion() {

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

		$this->parser->expects( $this->atLeastOnce() )
			->method( 'getOptions' )
			->will( $this->returnValue( $this->parserOptions ) );

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

}
