<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\ResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\ResultPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0
 */
class ResultPrinterTest extends TestCase {

	/**
	 * @dataProvider searchLabelProvider
	 */
	public function testLinkFurtherResultsBySearchLabel( $searchLabel, $expected ) {
		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'hasFurtherResults' )
			->willReturn( true );

		$instance = new class( 'test' ) extends ResultPrinter {

			protected function getResultText( QueryResult $res, $outputMode ) {
				return '';
			}

			public function linkFurtherResultsWith( array $params, QueryResult $results ) {
				$this->params = $params;
				$this->postProcessParameters();

				return $this->linkFurtherResults( $results );
			}
		};

		$this->assertSame(
			$expected,
			$instance->linkFurtherResultsWith( [ 'searchlabel' => $searchLabel ], $queryResult )
		);
	}

	public function searchLabelProvider() {
		yield [ 'Further results', true ];
		yield [ '', false ];
		// #6134
		yield [ '<!-- nothing -->', false ];
	}

}
