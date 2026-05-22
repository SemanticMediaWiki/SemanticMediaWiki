<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\User\User;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\Installer;
use SMW\Utils\CliMsgFormatter;

/**
 * Runs after the SMW install/setup has created its tables. Imports any
 * SMW-provided pages from `smwgImportFileDirs`.
 *
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::Installer::AfterCreateTablesComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class AfterCreateTablesComplete {

	/**
	 * @since 7.0.0
	 */
	public function onSMWSQLStoreInstallerAfterCreateTablesComplete( $tableBuilder, $messageReporter, $options ): bool {
		$messageReporter->reportMessage(
			( new CliMsgFormatter() )->section( 'Import task(s)', 3, '-', true )
		);

		$applicationFactory = ApplicationFactory::getInstance();
		$importerServiceFactory = $applicationFactory->create( 'ImporterServiceFactory' );

		$contentIterator = $importerServiceFactory->newJsonContentIterator(
			$applicationFactory->getSettings()->get( 'smwgImportFileDirs' )
		);

		$importer = $importerServiceFactory->newImporter(
			$contentIterator
		);

		$importer->isEnabled( $options->safeGet( Installer::RUN_IMPORT, false ) );
		$importer->setMessageReporter( $messageReporter );
		$importer->setImporter( User::MAINTENANCE_SCRIPT_USER );
		$importer->runImport();

		return true;
	}

}
