<?php

namespace SMW\Tests\SPARQLStore;

use SMW\DIWikiPage;
use SMW\Application;

use SMWQuery as Query;
use SMWValueDescription as ValueDescription;
use SMWConjunction as Conjunction;
use SMWDisjunction as Disjunction;
use SMWNamespaceDescription as NamespaceDescription;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class NullQueryResultTest extends \PHPUnit_Framework_TestCase {

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
			array_map( function ( $ns ) {
				return new NamespaceDescription( $ns );
			}, array( NS_MAIN ) )
		);

		$description = new Conjunction( array( $description, $namespacesDisjunction ) );

		$query->setDescription( $description );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			Application::getInstance()->getStore()->getQueryResult( $query )
		);
	}

}
