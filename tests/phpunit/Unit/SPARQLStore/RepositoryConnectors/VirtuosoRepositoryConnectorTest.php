<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class VirtuosoRepositoryConnectorTest extends ElementaryRepositoryConnectorTest {

	public function getRepositoryConnectors() {
		return [
			'SMWSparqlDatabaseVirtuoso',
			VirtuosoRepositoryConnector::class
		];
	}

}
