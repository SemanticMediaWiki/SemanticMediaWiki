<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FourstoreRepositoryConnectorTest extends ElementaryRepositoryConnectorTest {

	public function getRepositoryConnectors() {
		return [
			'SMWSparqlDatabase4Store',
			FourstoreRepositoryConnector::class
		];
	}

}
