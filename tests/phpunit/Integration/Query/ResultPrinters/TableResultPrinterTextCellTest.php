<?php

namespace SMW\Tests\Integration\Query\ResultPrinters;

use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use SMW\Query\QueryProcessor;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * Needs stored pages, a query and MediaWiki's message parse to render a table
 * as `Special:Ask` does. JSONScript cannot enable database messages, which a
 * customised `MediaWiki:Smw-parse` needs.
 *
 * @covers \SMW\Query\ResultPrinters\TableResultPrinter
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class TableResultPrinterTextCellTest extends SMWIntegrationTestCase {

	public function testPlainTextCellGoesThroughACustomisedParseMessage() {
		$this->overrideConfigValue( MainConfigNames::UseDatabaseMessages, true );
		$this->setTemporaryHook( 'MessagesPreLoad', static function ( $title, &$message ) {
			if ( $title === 'Smw-parse' ) {
				$message = '<span class="customised-cell">$1</span>';
			}
		} );

		$this->createPage( 'Property:Has cell text', '[[Has type::Text]]' );
		$this->createPage( 'Text cell page', '[[Has cell text::Plain cell words]] [[Category:Text cell test]]' );

		$this->assertStringContainsString(
			'<span class="customised-cell">Plain cell words</span>',
			$this->renderTableAsSpecialAsk( [ '[[Category:Text cell test]]', '?Has cell text' ] )
		);
	}

	private function createPage( string $title, string $text ): void {
		UtilityFactory::getInstance()->newPageCreator()
			->createPage( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $title ) )
			->doEdit( $text );
	}

	private function renderTableAsSpecialAsk( array $rawParameters ): string {
		[ $query, $params ] = QueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParameters,
			SMW_OUTPUT_HTML,
			QueryProcessor::SPECIAL_PAGE,
			false
		);

		return QueryProcessor::getResultPrinter( 'table', QueryProcessor::SPECIAL_PAGE )
			->getResult( $this->getStore()->getQueryResult( $query ), $params, SMW_OUTPUT_HTML );
	}

}
