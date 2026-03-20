<?php

namespace SMW\Tests\Integration\Query;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class NullQueryResultTest extends TestCase {

	public function testNullQueryResult() {
		$term = '[[Some_string_to_query]]';

		$description = new ValueDescription(
			new WikiPage( $term, NS_MAIN ),
			null
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$description = $query->getDescription();

		$namespacesDisjunction = new Disjunction(
			array_map( static function ( $ns ) {
				return new NamespaceDescription( $ns );
			}, [ NS_MAIN ] )
		);

		$description = new Conjunction( [ $description, $namespacesDisjunction ] );

		$query->setDescription( $description );

		$this->assertInstanceOf(
			QueryResult::class,
			ApplicationFactory::getInstance()->getStore()->getQueryResult( $query )
		);
	}

}
