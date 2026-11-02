<?php

namespace SMW\Tests\Integration\Query;

use SMW\Query\Parser\LegacyParser;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Needs the database because parsing a condition looks up the type of its property in the store.
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.4
 */
class QueryStringRoundTripTest extends SMWIntegrationTestCase {

	/**
	 * @dataProvider orQueryProvider
	 */
	public function testQueryStringOfOrQueryParsesToTheSameQuery( string $query ) {
		$description = $this->newQueryParser()->getQueryDescription( $query );

		$parser = $this->newQueryParser();
		$reparsed = $parser->getQueryDescription( $description->getQueryString() );

		$this->assertSame( [], $parser->getErrors() );
		$this->assertSame( $description->getFingerprint(), $reparsed->getFingerprint() );
	}

	public static function orQueryProvider(): iterable {
		yield 'stand-alone OR' => [ '[[A::1]] OR [[B::2]]' ];

		yield 'OR in a conjunction' => [ '[[Category:X]] <q>[[A::1]] OR [[B::2]]</q>' ];

		yield 'OR in a conjunction used as a property value' => [ '[[P::<q>[[Category:X]] <q>[[A::1]] OR [[B::2]]</q></q>]]' ];

		yield 'OR with a subquery at the default depth limit' => [
			'[[P::<q>[[Q::<q>[[R::<q>[[S::1]] [[T::1]]</q>]] [[U::1]]</q>]] [[V::1]]</q>]] OR [[B::2]]'
		];
	}

	private function newQueryParser(): LegacyParser {
		return ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser();
	}

}
