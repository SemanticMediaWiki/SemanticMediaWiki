<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Query\QueryContext;
use SMW\Query\QueryResult;
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
