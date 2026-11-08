<?php

namespace SMW\Tests\Unit\Query\ResultPrinters\ListResultPrinter;

use MediaWiki\Linker\Linker;
use PHPUnit\Framework\TestCase;
use SMW\DataValues\DataValue;
use SMW\Query\PrintRequest;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\Query\Result\ResultArray;
use SMW\Query\ResultPrinters\ListResultPrinter\ListResultBuilder;

/**
 * @covers \SMW\Query\ResultPrinters\ListResultPrinter\ListResultBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class ListResultBuilderTest extends TestCase {

	private const PAYLOAD = '"><img src=x onerror=alert(1)>';
	private const PAYLOAD_ESCAPED = '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;';

	public function testMarkupSeparatorIsEscapedInHtmlOutput() {
		$builder = $this->newBuilderWithTwoRows();
		$builder->set( 'sep', self::PAYLOAD );

		$text = $builder->getResultText( SMW_OUTPUT_HTML );

		$this->assertStringContainsString( self::PAYLOAD_ESCAPED, $text );
		$this->assertStringNotContainsString( self::PAYLOAD, $text );
	}

	public function testOutputModeDefaultsToHtmlWhenOmitted() {
		$builder = $this->newBuilderWithTwoRows();
		$builder->set( 'sep', self::PAYLOAD );

		$text = $builder->getResultText();

		$this->assertStringContainsString( self::PAYLOAD_ESCAPED, $text );
		$this->assertStringNotContainsString( self::PAYLOAD, $text );
	}

	public function testMarkupSeparatorIsEscapedInRawOutput() {
		$builder = $this->newBuilderWithTwoRows();
		$builder->set( 'sep', self::PAYLOAD );

		$text = $builder->getResultText( SMW_OUTPUT_RAW );

		$this->assertStringContainsString( self::PAYLOAD_ESCAPED, $text );
		$this->assertStringNotContainsString( self::PAYLOAD, $text );
	}

	public function testMarkupSeparatorIsPreservedForWikiOutput() {
		$builder = $this->newBuilderWithTwoRows();
		$builder->set( 'sep', self::PAYLOAD );

		$text = $builder->getResultText( SMW_OUTPUT_WIKI );

		// Inline #ask output is sanitised downstream by the parser, so the raw
		// separator is preserved rather than escaped.
		$this->assertStringContainsString( self::PAYLOAD, $text );
	}

	public function testBenignSeparatorRoundTripsUnchanged() {
		$builder = $this->newBuilderWithTwoRows();
		$builder->set( 'sep', ', ' );

		$text = $builder->getResultText( SMW_OUTPUT_HTML );

		$this->assertStringContainsString( 'FirstValue', $text );
		$this->assertStringContainsString( 'SecondValue', $text );
		$this->assertStringContainsString( 'FirstValue</span></span></span>, <span', $text );
	}

	private function newBuilderWithTwoRows(): ListResultBuilder {
		$query = $this->createMock( Query::class );
		$query->method( 'getOffset' )->willReturn( 0 );

		$queryResult = $this->createMock( QueryResult::class );
		$queryResult->method( 'getQuery' )->willReturn( $query );
		$queryResult->method( 'getNext' )->willReturnOnConsecutiveCalls(
			[ $this->newField( 'FirstValue' ) ],
			[ $this->newField( 'SecondValue' ) ],
			false
		);

		$builder = new ListResultBuilder( $queryResult, $this->createMock( Linker::class ), false );
		$builder->set( 'offset', 0 );

		return $builder;
	}

	private function newField( string $valueText ): ResultArray {
		$value = $this->createMock( DataValue::class );
		$value->method( 'getShortText' )->willReturn( $valueText );

		$printRequest = $this->createMock( PrintRequest::class );
		$printRequest->method( 'getLabel' )->willReturn( '' );

		$field = $this->createMock( ResultArray::class );
		$field->method( 'getPrintRequest' )->willReturn( $printRequest );
		$field->method( 'getNextDataValue' )->willReturnOnConsecutiveCalls( $value, false );

		return $field;
	}

}
