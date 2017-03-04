<?php

namespace SMW\Services;

use SMW\Importer\ContentsImporter;
use SMW\Importer\ImportContentsIterator;
use SMW\Importer\JsonImportContentsIterator;
use SMW\Importer\JsonImportContentsFileDirReader;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed either via the
 * ApplicationFactory or a different factory instance.
 *
 * @license GNU GPL v2
 * @since 2.5
 *
 * @author mwjames
 */
return array(

	/**
	 * ContentsImporter
	 *
	 * @return callable
	 */
	'ContentsImporter' => function( $containerBuilder, ImportContentsIterator $importContentsIterator  ) {
		$containerBuilder->registerExpectedReturnType( 'ContentsImporter', '\SMW\Importer\ContentsImporter' );

		$fileImporter = new ContentsImporter(
			$importContentsIterator,
			$containerBuilder->create( 'PageCreator' )
		);

		$fileImporter->setReqVersion(
			$containerBuilder->singleton( 'Settings' )->get( 'smwgImportReqVersion' )
		);

		return $fileImporter;
	},

	/**
	 * JsonImportContentsIterator
	 *
	 * @return callable
	 */
	'JsonImportContentsIterator' => function( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'JsonImportContentsIterator', '\SMW\Importer\JsonImportContentsIterator' );

		$JsonImportContentsFileDirReader = new JsonImportContentsFileDirReader(
			$containerBuilder->singleton( 'Settings' )->get( 'smwgImportFileDir' )
		);

		return new JsonImportContentsIterator( $JsonImportContentsFileDirReader );
	},

	/**
	 * ContentsImporter
	 *
	 * @return callable
	 */
	'JsonContentsImporter' => function( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'JsonContentsImporter', '\SMW\Importer\ContentsImporter' );
		return $containerBuilder->create( 'ContentsImporter', $containerBuilder->create( 'JsonImportContentsIterator' ) );
	},

);