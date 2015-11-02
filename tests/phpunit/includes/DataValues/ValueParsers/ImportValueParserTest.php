<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ValueParsers\ImportValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\ImportValueParser
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ImportValueParserTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$controlledVocabularyImportContentFetcher = $this->getMockBuilder( '\SMW\ControlledVocabularyImportContentFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\ImportValueParser',
			new ImportValueParser( $controlledVocabularyImportContentFetcher )
		);
	}

	public function testTryParseForInvalidValueFormat() {

		$controlledVocabularyImportContentFetcher = $this->getMockBuilder( '\SMW\ControlledVocabularyImportContentFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ImportValueParser( $controlledVocabularyImportContentFetcher );
		$instance->parse( 'incorrectFormat' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testTryParseForValidValueFormatErroredByNonExistingImportEntry() {

		$controlledVocabularyImportContentFetcher = $this->getMockBuilder( '\SMW\ControlledVocabularyImportContentFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$controlledVocabularyImportContentFetcher->expects( $this->once() )
			->method( 'contains' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( false ) ); // mock non-existing entry

		$instance = new ImportValueParser( $controlledVocabularyImportContentFetcher );
		$instance->parse( 'Foo:bar' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider invalidUriContent
	 */
	public function testTryParseForValidValueFormatErroredByUriMismatch( $content ) {

		$controlledVocabularyImportContentFetcher = $this->getMockBuilder( '\SMW\ControlledVocabularyImportContentFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$controlledVocabularyImportContentFetcher->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$controlledVocabularyImportContentFetcher->expects( $this->once() )
			->method( 'fetchFor' )
			->will( $this->returnValue( $content ) );

		$instance = new ImportValueParser( $controlledVocabularyImportContentFetcher );
		$instance->parse( 'Foo:bar' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider invalidTypeContent
	 */
	public function testTryParseForValidValueFormatErroredByTypeMismatch( $content, $typelist ) {

		$controlledVocabularyImportContentFetcher = $this->getMockBuilder( '\SMW\ControlledVocabularyImportContentFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$controlledVocabularyImportContentFetcher->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$controlledVocabularyImportContentFetcher->expects( $this->once() )
			->method( 'fetchFor' )
			->will( $this->returnValue( $content ) );

		$instance = new ImportValueParser( $controlledVocabularyImportContentFetcher );
		$instance->parse( 'Foo:bar' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider validMatchTypeContent
	 */
	public function testParseForValidValueToMatchType( $content, $parseValue, $expected ) {

		$controlledVocabularyImportContentFetcher = $this->getMockBuilder( '\SMW\ControlledVocabularyImportContentFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$controlledVocabularyImportContentFetcher->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$controlledVocabularyImportContentFetcher->expects( $this->once() )
			->method( 'fetchFor' )
			->will( $this->returnValue( $content ) );

		$instance = new ImportValueParser( $controlledVocabularyImportContentFetcher );
		$result = $instance->parse( $parseValue );

		$this->assertEmpty(
			$instance->getErrors()
		);

		foreach ( $result as $key => $value ) {
			$this->assertEquals(
				$expected[$key],
				$value
			);
		}
	}

	public function invalidUriContent() {

		$provider[] = array(
			''
		);

		// Missing head
		$provider[] = array(
			"Foo\n name|Type:Text\n"
		);

		return $provider;
	}

	public function invalidTypeContent() {

		// Url missing
		$provider[] = array(
			'|[http://www.foaf-project.org/ Friend Of A Friend]\n name',
			array()
		);

		// Type missing
		$provider[] = array(
			'http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name',
			array()
		);

		// Cannot match section name
		$provider[] = array(
			"http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name|Type:Text\n",
			array( 'name' => 'Type:Text' )
		);

		return $provider;
	}

	public function validMatchTypeContent() {

		#0
		$provider[] = array(
			"http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name|Type:Text\n",
			'Foaf:name',
			array(
				'Foaf',
				'name',
				'http://xmlns.com/foaf/0.1/',
				'[http://www.foaf-project.org/ Friend Of A Friend]',
				'Type:Text'
			)
		);

		#1
		$provider[] = array(
			" http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n   name|Type:Text\n",
			'Foaf:name',
			array(
				'Foaf',
				'name',
				'http://xmlns.com/foaf/0.1/',
				'[http://www.foaf-project.org/ Friend Of A Friend]',
				'Type:Text'
			)
		);

		#2 mbox_sha1sum
		$provider[] = array(
			" http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n   mbox_sha1sum|Type:Text\n",
			'Foaf:mbox_sha1sum',
			array(
				'Foaf',
				'mbox_sha1sum',
				'http://xmlns.com/foaf/0.1/',
				'[http://www.foaf-project.org/ Friend Of A Friend]',
				'Type:Text'
			)
		);

		return $provider;
	}

}
