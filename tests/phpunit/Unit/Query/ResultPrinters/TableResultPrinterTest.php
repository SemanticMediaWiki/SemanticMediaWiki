<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\ResultPrinter;
use SMW\Query\ResultPrinters\TableResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\TableResultPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TableResultPrinterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TableResultPrinter::class,
			new TableResultPrinter( 'table' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new TableResultPrinter( 'table' );

		$this->assertIsString(

			$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI )
		);
	}

	public function testDependsOnUserLanguage_ReturnsFalse() {
		$instance = new TableResultPrinter( 'table' );

		$this->assertFalse( $instance->dependsOnUserLanguage() );
	}

	/**
	 * @dataProvider valueSeparatorProvider
	 */
	public function testValueSeparatorEscapesHtmlOutput( string $sep, int $outputMode, string $expected ) {
		$instance = new TableResultPrinter( 'table' );

		$params = new \ReflectionProperty( ResultPrinter::class, 'params' );
		$params->setAccessible( true );
		$params->setValue( $instance, [ 'sep' => $sep ] );

		$method = new \ReflectionMethod( TableResultPrinter::class, 'getValueSeparator' );
		$method->setAccessible( true );

		$this->assertSame( $expected, $method->invoke( $instance, $outputMode ) );
	}

	public static function valueSeparatorProvider(): iterable {
		// HTML output (Special:Ask): raw markup would be an XSS sink, so the
		// separator is escaped, allowlisting only the <br> line-break variants.
		yield 'html escapes script' => [
			"<script>alert('x')</script>", SMW_OUTPUT_HTML,
			'&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;',
		];
		yield 'html escapes attribute breakout' => [
			'"><img src=x onerror=alert(1)>', SMW_OUTPUT_HTML,
			'&quot;&gt;&lt;img src=x onerror=alert(1)&gt;',
		];
		yield 'html keeps <br>' => [ '<br>', SMW_OUTPUT_HTML, '<br>' ];
		yield 'html keeps <br/>' => [ '<br/>', SMW_OUTPUT_HTML, '<br/>' ];
		yield 'html keeps <br />' => [ '<br />', SMW_OUTPUT_HTML, '<br />' ];
		yield 'html keeps <BR> case-insensitively' => [ '<BR>', SMW_OUTPUT_HTML, '<BR>' ];

		// Wiki output (inline #ask): the surrounding parser sanitises the
		// result and escaping would corrupt legitimate wikitext, so the raw
		// separator is preserved unchanged.
		yield 'wiki passes script through' => [
			"<script>alert('x')</script>", SMW_OUTPUT_WIKI,
			"<script>alert('x')</script>",
		];
		yield 'wiki passes <br> through' => [ '<br>', SMW_OUTPUT_WIKI, '<br>' ];

		// RAW output (Special:Ask request_type=raw, used by remote requests)
		// and FILE output are also emitted into an HTML context without parser
		// sanitisation, so they must escape exactly like HTML output.
		yield 'raw escapes script' => [
			"<script>alert('x')</script>", SMW_OUTPUT_RAW,
			'&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;',
		];
		yield 'raw keeps <br>' => [ '<br>', SMW_OUTPUT_RAW, '<br>' ];
		yield 'file escapes script' => [
			"<script>alert('x')</script>", SMW_OUTPUT_FILE,
			'&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;',
		];
	}

}
