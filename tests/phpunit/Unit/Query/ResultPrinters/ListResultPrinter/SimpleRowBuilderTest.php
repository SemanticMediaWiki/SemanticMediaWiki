<?php

namespace SMW\Tests\Unit\Query\ResultPrinters\ListResultPrinter;

use PHPUnit\Framework\TestCase;
use SMW\Query\PrintRequest;
use SMW\Query\Result\ResultArray;
use SMW\Query\ResultPrinters\ListResultPrinter\ParameterDictionary;
use SMW\Query\ResultPrinters\ListResultPrinter\SimpleRowBuilder;
use SMW\Query\ResultPrinters\ListResultPrinter\ValueTextsBuilder;

/**
 * @covers \SMW\Query\ResultPrinters\ListResultPrinter\SimpleRowBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class SimpleRowBuilderTest extends TestCase {

	private const PAYLOAD = '"><img src=x onerror=alert(1)>';
	private const PAYLOAD_ESCAPED = '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;';

	public function testMarkupPropertySeparatorIsEscapedInHtmlOutput() {
		$text = $this->buildRowText( self::PAYLOAD, SMW_OUTPUT_HTML );

		$this->assertStringContainsString( 'Second' . self::PAYLOAD_ESCAPED . 'Third', $text );
		$this->assertStringNotContainsString( self::PAYLOAD, $text );
	}

	public function testMarkupPropertySeparatorIsPreservedForWikiOutput() {
		$text = $this->buildRowText( self::PAYLOAD, SMW_OUTPUT_WIKI );

		$this->assertStringContainsString( 'Second' . self::PAYLOAD . 'Third', $text );
	}

	public function testBenignPropertySeparatorRoundTripsUnchanged() {
		$text = $this->buildRowText( ', ', SMW_OUTPUT_HTML );

		$this->assertStringContainsString( 'Second, Third', $text );
	}

	private function buildRowText( string $propsep, int $outputMode ): string {
		$builder = new SimpleRowBuilder();

		$configuration = new ParameterDictionary();
		$configuration->set( 'propsep', $propsep );
		$configuration->set( 'output-mode', $outputMode );
		$builder->setConfiguration( $configuration );

		// The field-value rendering is exercised elsewhere; here it is stubbed so
		// only the property separator between the non-subject fields is asserted.
		$valueTextsBuilder = $this->createMock( ValueTextsBuilder::class );
		$valueTextsBuilder->method( 'getValuesText' )->willReturnOnConsecutiveCalls(
			'First', 'Second', 'Third'
		);
		$builder->setValueTextsBuilder( $valueTextsBuilder );

		return $builder->getRowText(
			[ $this->newField(), $this->newField(), $this->newField() ]
		);
	}

	private function newField(): ResultArray {
		$printRequest = $this->createMock( PrintRequest::class );
		$printRequest->method( 'getLabel' )->willReturn( '' );

		$field = $this->createMock( ResultArray::class );
		$field->method( 'getPrintRequest' )->willReturn( $printRequest );

		return $field;
	}

}
