<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ImportValue;
use SMW\DataValues\ValueParsers\ImportValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\ImportValueParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ImportValueParserTest extends \PHPUnit_Framework_TestCase {

	private $mediaWikiNsContentReader;

	protected function setUp() {
		parent::setUp();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ImportValueParser::class,
			new ImportValueParser( $this->mediaWikiNsContentReader )
		);
	}

	public function testTryParseForInvalidValueFormat() {

		$instance = new ImportValueParser( $this->mediaWikiNsContentReader );
		$instance->parse( 'incorrectFormat' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testTryParseForValidValueFormatErroredByNonExistingImportEntry() {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->with( $this->equalTo( ImportValue::IMPORT_PREFIX . 'Foo' ) )
			->will( $this->returnValue( false ) );

		$instance = new ImportValueParser(
			$this->mediaWikiNsContentReader
		);

		$instance->parse( 'Foo:bar' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider invalidUriContent
	 */
	public function testTryParseForValidValueFormatErroredByUriMismatch( $content ) {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $content ) );

		$instance = new ImportValueParser(
			$this->mediaWikiNsContentReader
		);

		$instance->parse( 'Foo:bar' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider invalidTypeContent
	 */
	public function testTryParseForValidValueFormatErroredByTypeMismatch( $content, $typelist ) {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $content ) );

		$instance = new ImportValueParser(
			$this->mediaWikiNsContentReader
		);

		$instance->parse( 'Foo:bar' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider validMatchTypeContent
	 */
	public function testParseForValidValueToMatchType( $content, $parseValue, $expected ) {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $content ) );

		$instance = new ImportValueParser(
			$this->mediaWikiNsContentReader
		);

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

		$provider[] = [
			''
		];

		// Missing head
		$provider[] = [
			"Foo\n name|Type:Text\n"
		];

		return $provider;
	}

	public function invalidTypeContent() {

		// Url missing
		$provider[] = [
			'|[http://www.foaf-project.org/ Friend Of A Friend]\n name',
			[]
		];

		// Type missing
		$provider[] = [
			'http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name',
			[]
		];

		// Cannot match section name
		$provider[] = [
			"http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name|Type:Text\n",
			[ 'name' => 'Type:Text' ]
		];

		$provider[] = [
			'',
			[]
		];

		$provider[] = [
			' ',
			[]
		];

		return $provider;
	}

	public function validMatchTypeContent() {

		#0
		$provider[] = [
			"http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name|Type:Text\n",
			'Foaf:name',
			[
				'Foaf',
				'name',
				'http://xmlns.com/foaf/0.1/',
				'[http://www.foaf-project.org/ Friend Of A Friend]',
				'Type:Text'
			]
		];

		#1
		$provider[] = [
			" http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n   name|Type:Text\n",
			'Foaf:name',
			[
				'Foaf',
				'name',
				'http://xmlns.com/foaf/0.1/',
				'[http://www.foaf-project.org/ Friend Of A Friend]',
				'Type:Text'
			]
		];

		#2 mbox_sha1sum
		$provider[] = [
			" http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n   mbox_sha1sum|Type:Text\n",
			'Foaf:mbox_sha1sum',
			[
				'Foaf',
				'mbox_sha1sum',
				'http://xmlns.com/foaf/0.1/',
				'[http://www.foaf-project.org/ Friend Of A Friend]',
				'Type:Text'
			]
		];

		return $provider;
	}

}
