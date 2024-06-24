<?php

namespace SMW\Tests;

use BacklinkCache;
use HashBagOStuff;
use MediaWiki\MediaWikiServices;
use ObjectCache;
use PHPUnit\Framework\TestResult;
use RequestContext;
use RuntimeException;
use SMW\DataValueFactory;
use SMW\MediaWiki\LinkBatch;
use SMW\PropertyRegistry;
use SMW\Services\ServicesFactory;
use SMW\StoreFactory;
use SMW\Tests\Utils\Connection\TestDatabaseTableBuilder;
use SMWExporter as Exporter;
use SMWQueryProcessor;
use Title;

/**
 * @group semantic-mediawiki
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
abstract class DatabaseTestCase extends SMWIntegrationTestCase {

}
