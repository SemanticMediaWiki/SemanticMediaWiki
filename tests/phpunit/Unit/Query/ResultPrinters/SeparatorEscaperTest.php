<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Query\ResultPrinters\SeparatorEscaper;

/**
 * @covers \SMW\Query\ResultPrinters\SeparatorEscaper
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class SeparatorEscaperTest extends TestCase {

	/**
	 * @dataProvider separatorProvider
	 */
	public function testEscape( string $separator, int $outputMode, string $expected ) {
		$this->assertSame( $expected, SeparatorEscaper::escape( $separator, $outputMode ) );
	}

	public static function separatorProvider(): iterable {
		// HTML output (Special:Ask) is emitted without parser sanitisation, so a
		// raw separator would be an XSS sink and is escaped.
		yield 'html escapes script' => [
			"<script>alert('x')</script>", SMW_OUTPUT_HTML,
			'&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;',
		];
		yield 'html escapes attribute breakout' => [
			'"><img src=x onerror=alert(1)>', SMW_OUTPUT_HTML,
			'&quot;&gt;&lt;img src=x onerror=alert(1)&gt;',
		];

		// The <br> line-break variants are the intended separator markup and are
		// allowlisted unchanged.
		yield 'html keeps <br>' => [ '<br>', SMW_OUTPUT_HTML, '<br>' ];
		yield 'html keeps <br/>' => [ '<br/>', SMW_OUTPUT_HTML, '<br/>' ];
		yield 'html keeps <br />' => [ '<br />', SMW_OUTPUT_HTML, '<br />' ];
		yield 'html keeps <BR> case-insensitively' => [ '<BR>', SMW_OUTPUT_HTML, '<BR>' ];

		// RAW output (Special:Ask request_type=raw) and FILE output land in an
		// HTML context without parser sanitisation and escape like HTML output.
		yield 'raw escapes attribute breakout' => [
			'"><img src=x onerror=alert(1)>', SMW_OUTPUT_RAW,
			'&quot;&gt;&lt;img src=x onerror=alert(1)&gt;',
		];
		yield 'raw keeps <br>' => [ '<br>', SMW_OUTPUT_RAW, '<br>' ];
		yield 'file escapes script' => [
			'<script>alert(1)</script>', SMW_OUTPUT_FILE,
			'&lt;script&gt;alert(1)&lt;/script&gt;',
		];

		// Wiki output (inline #ask) is sanitised downstream by the parser, so the
		// raw separator is preserved and legitimate wikitext keeps working.
		yield 'wiki passes script through' => [
			"<script>alert('x')</script>", SMW_OUTPUT_WIKI,
			"<script>alert('x')</script>",
		];
		yield 'wiki passes <br> through' => [ '<br>', SMW_OUTPUT_WIKI, '<br>' ];

		// A benign separator round-trips unchanged in every mode: no double escaping.
		yield 'html leaves comma separator unchanged' => [ ', ', SMW_OUTPUT_HTML, ', ' ];
		yield 'wiki leaves comma separator unchanged' => [ ', ', SMW_OUTPUT_WIKI, ', ' ];
	}

}
