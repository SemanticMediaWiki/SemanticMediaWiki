<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;
use SMW\Query\PrintRequest;
use SMW\Query\QueryContext;
use SMW\Query\QueryResult;
use SMW\Query\Result\ResultArray;
use SMW\Query\ResultPrinters\CategoryResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\CategoryResultPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CategoryResultPrinterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CategoryResultPrinter::class,
			new CategoryResultPrinter( 'category' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new CategoryResultPrinter( 'category' );

		$this->assertIsString(

			$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI )
		);
	}

	public function testDependsOnUserLanguage_ReturnsFalse() {
		$instance = new CategoryResultPrinter( 'category' );

		$this->assertFalse( $instance->dependsOnUserLanguage() );
	}

	public function testContinueAbbrevIsDeferredMarkerForInlineQuery() {
		$instance = $this->newAccessiblePrinter();
		$instance->setContext( QueryContext::INLINE_QUERY );

		$this->assertStringContainsString(
			'smw-localized-message',
			$instance->getContinueAbbrev()
		);
	}

	public function testContinueAbbrevIsLocalizedTextForSpecialPage() {
		$instance = $this->newAccessiblePrinter();
		$instance->setContext( QueryContext::SPECIAL_PAGE );

		$this->assertStringNotContainsString(
			'smw-localized-message',
			$instance->getContinueAbbrev()
		);
	}

	public function testContinueAbbrevIsLocalizedTextByDefault() {
		$instance = $this->newAccessiblePrinter();

		$this->assertStringNotContainsString(
			'smw-localized-message',
			$instance->getContinueAbbrev()
		);
	}

	public function testMarkupDelimiterIsEscapedInHtmlOutput() {
		$result = $this->buildRowContents(
			'"><img src=x onerror=alert(1)>', SMW_OUTPUT_HTML
		);

		$this->assertStringContainsString( '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;', $result );
		$this->assertStringNotContainsString( '"><img src=x onerror=alert(1)>', $result );
	}

	public function testMarkupDelimiterIsPreservedForWikiOutput() {
		$result = $this->buildRowContents(
			'"><img src=x onerror=alert(1)>', SMW_OUTPUT_WIKI
		);

		// Inline #ask output is sanitised downstream by the parser.
		$this->assertStringContainsString( '"><img src=x onerror=alert(1)>', $result );
	}

	public function testMarkupDelimiterIsEscapedInTemplateOutput() {
		$fields = $this->buildTemplateFields(
			'"><img src=x onerror=alert(1)>', SMW_OUTPUT_HTML
		);

		$this->assertStringContainsString( '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;', $fields['1'] );
		$this->assertStringNotContainsString( '"><img src=x onerror=alert(1)>', $fields['1'] );
	}

	private function buildTemplateFields( string $delim, int $outputMode ): array {
		$instance = new CategoryResultPrinter( 'category' );

		$this->setPrivate( $instance, 'delim', $delim );
		$this->setPrivate( $instance, 'params', [ 'named args' => false ] );

		$templateRenderer = new class extends WikitextTemplateRenderer {
			public array $captured = [];

			public function addField( $field, $value ): void {
				$this->captured[$field] = $value;
			}
		};

		$this->setPrivate( $instance, 'templateRenderer', $templateRenderer );

		$method = new \ReflectionMethod( CategoryResultPrinter::class, 'row_to_template' );
		$method->setAccessible( true );

		$firstCol = true;
		$method->invokeArgs(
			$instance, [ [ $this->newField( 'ValA', 'ValB' ) ], &$firstCol, $outputMode ]
		);

		return $templateRenderer->captured;
	}

	private function setPrivate( object $instance, string $property, $value ): void {
		$reflection = new \ReflectionProperty( CategoryResultPrinter::class, $property );
		$reflection->setAccessible( true );
		$reflection->setValue( $instance, $value );
	}

	private function buildRowContents( string $delim, int $outputMode ): string {
		$instance = new CategoryResultPrinter( 'category' );

		$delimProperty = new \ReflectionProperty( CategoryResultPrinter::class, 'delim' );
		$delimProperty->setAccessible( true );
		$delimProperty->setValue( $instance, $delim );

		$method = new \ReflectionMethod( CategoryResultPrinter::class, 'row_to_contents' );
		$method->setAccessible( true );

		$firstCol = true;

		return $method->invokeArgs(
			$instance, [ [ $this->newField( 'ValA', 'ValB' ) ], &$firstCol, $outputMode ]
		);
	}

	private function newField( string ...$values ): ResultArray {
		$printRequest = $this->createMock( PrintRequest::class );
		$printRequest->method( 'getLabel' )->willReturn( '' );

		$field = $this->createMock( ResultArray::class );
		$field->method( 'getPrintRequest' )->willReturn( $printRequest );
		$field->method( 'getNextText' )->willReturnOnConsecutiveCalls( ...[ ...$values, false ] );

		return $field;
	}

	/**
	 * Exposes the protected getContinueAbbrev() seam for assertion.
	 */
	private function newAccessiblePrinter(): CategoryResultPrinter {
		return new class( 'category' ) extends CategoryResultPrinter {
			public function getContinueAbbrev(): string {
				return parent::getContinueAbbrev();
			}
		};
	}

}
