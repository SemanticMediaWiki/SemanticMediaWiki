<?php

namespace SMW\Tests\Utils\Mock;

use SMWQuery;
use SMWQueryResult;
use SMWSQLStore3;

/**
 * FIXME One would wish to have a FakeStore but instead SMWSQLStore3 is used in
 * order to avoid to implement all abstract methods specified by SMW\Store
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since   1.9.2
 *
 * @author mwjames
 */
class FakeQueryStore extends SMWSQLStore3 {

	protected $queryResult;

	public function setQueryResult( SMWQueryResult $queryResult ) {
		$this->queryResult = $queryResult;
	}
	// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.CodeAnalysis.UnusedFunctionParameter
	public function getQueryResult( SMWQuery $query ) { // @codingStandardsIgnoreEnd
		return $this->queryResult;
	}
}
