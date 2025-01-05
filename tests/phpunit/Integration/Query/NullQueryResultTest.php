<?php

namespace SMW\Tests\Integration\Query;

use SMW\DIWikiPage;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMWQuery as Query;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class NullQueryResultTest extends \PHPUnit\Framework\TestCase {

	public function testNullQueryResult() {
		$term = '[[Some_string_to_query]]';

		$description = new ValueDescription(
			new DIWikiPage( $term, NS_MAIN ),
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
			'\SMWQueryResult',
			ApplicationFactory::getInstance()->getStore()->getQueryResult( $query )
		);
	}

}
