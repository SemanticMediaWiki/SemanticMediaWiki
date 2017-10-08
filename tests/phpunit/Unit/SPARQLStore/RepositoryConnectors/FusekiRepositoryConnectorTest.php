<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FusekiRepositoryConnectorTest extends ElementaryRepositoryConnectorTest {

	public function getRepositoryConnectors() {
		return [
			FusekiRepositoryConnector::class
		];
	}

}
