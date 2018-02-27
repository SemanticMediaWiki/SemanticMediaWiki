<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class GenericRepositoryConnectorTest extends ElementaryRepositoryConnectorTest {

	public function getRepositoryConnectors() {
		return [
			GenericRepositoryConnector::class
		];
	}

}
