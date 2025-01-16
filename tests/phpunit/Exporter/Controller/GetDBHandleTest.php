<?php

namespace SMW\Tests\Integration;

use MediaWiki\MediaWikiServices;
use SMW\Exporter\SMWExportController;
use Wikimedia\Rdbms\IDatabase;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 5.0-alpha
 */
class SMWExportControllerIntegrationTest extends TestCase {

    /**
     * @covers \SMW\Exporter\SMWExportController::getDBHandle
     */
    public function testGetDBHandle() {
        $dbHandle = SMWExportController::getDBHandle();
        $this->assertInstanceOf(IDatabase::class, $dbHandle);
    }

    /**
     * @covers \SMW\Exporter\SMWExportController::getDBHandle
     */
    public function testGetDBHandleWithDifferentVersions() {
        $originalVersion = MW_VERSION;

        // Test with version >= 1.42
        define('MW_VERSION', '1.42');
        $dbHandle = SMWExportController::getDBHandle();
        $this->assertInstanceOf(IDatabase::class, $dbHandle);

        // Test with version < 1.42
        define('MW_VERSION', '1.41');
        $dbHandle = SMWExportController::getDBHandle();
        $this->assertInstanceOf(IDatabase::class, $dbHandle);

        // Restore original version
        define('MW_VERSION', $originalVersion);
    }
}