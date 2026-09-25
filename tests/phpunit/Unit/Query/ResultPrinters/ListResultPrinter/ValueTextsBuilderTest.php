<?php

namespace SMW\Tests\Unit\Query\ResultPrinters\ListResultPrinter;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\DataValue;
use SMW\Query\Result\ResultArray;
use SMW\Query\ResultPrinters\ListResultPrinter\ParameterDictionary;
use SMW\Query\ResultPrinters\ListResultPrinter\ValueTextsBuilder;
use SMW\Query\ResultPrinters\PrefixParameterProcessor;

/**
 * @covers \SMW\Query\ResultPrinters\ListResultPrinter\ValueTextsBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class ValueTextsBuilderTest extends TestCase {

	private const PAYLOAD = '"><img src=x onerror=alert(1)>';
	private const PAYLOAD_ESCAPED = '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;';

	public function testMarkupValueSeparatorIsEscapedInHtmlOutput() {
		$text = $this->buildValuesText( self::PAYLOAD, SMW_OUTPUT_HTML );

		$this->assertSame( 'First' . self::PAYLOAD_ESCAPED . 'Second', $text );
	}

	public function testMarkupValueSeparatorIsPreservedForWikiOutput() {
		$text = $this->buildValuesText( self::PAYLOAD, SMW_OUTPUT_WIKI );

		$this->assertSame( 'First' . self::PAYLOAD . 'Second', $text );
	}

	public function testBenignValueSeparatorRoundTripsUnchanged() {
		$text = $this->buildValuesText( ', ', SMW_OUTPUT_HTML );

		$this->assertSame( 'First, Second', $text );
	}

	private function buildValuesText( string $valuesep, int $outputMode ): string {
		$prefixParameterProcessor = $this->createMock( PrefixParameterProcessor::class );
		$prefixParameterProcessor->method( 'useLongText' )->willReturn( false );

		$builder = new ValueTextsBuilder( $prefixParameterProcessor );

		$configuration = new ParameterDictionary();
		$configuration->set( 'valuesep', $valuesep );
		$configuration->set( 'output-mode', $outputMode );
		$builder->setConfiguration( $configuration );

		return $builder->getValuesText( $this->newFieldWithValues( 'First', 'Second' ) );
	}

	private function newFieldWithValues( string $first, string $second ): ResultArray {
		$field = $this->createMock( ResultArray::class );
		$field->method( 'getNextDataValue' )->willReturnOnConsecutiveCalls(
			$this->newValue( $first ),
			$this->newValue( $second ),
			false
		);

		return $field;
	}

	private function newValue( string $text ): DataValue {
		$value = $this->createMock( DataValue::class );
		$value->method( 'getShortText' )->willReturn( $text );

		return $value;
	}

}
