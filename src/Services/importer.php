<?php

namespace SMW\Services;

use SMW\Importer\ContentCreators\DispatchingContentCreator;
use SMW\Importer\ContentCreators\TextContentCreator;
use SMW\Importer\ContentCreators\XmlContentCreator;
use SMW\Importer\ContentIterator;
use SMW\Importer\ContentModeller;
use SMW\Importer\Importer;
use SMW\Importer\JsonContentIterator;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Utils\File;
use SMW\Utils\FileFetcher;

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
return [

	/**
	 * ImporterServiceFactory
	 *
	 * @return callable
	 */
	'ImporterServiceFactory' => static function ( $callbackContainerBuilder ) {
		$callbackContainerBuilder->registerExpectedReturnType( 'ImporterServiceFactory', ImporterServiceFactory::class );
		return new ImporterServiceFactory( $callbackContainerBuilder );
	},

	/**
	 * XmlContentCreator
	 *
	 * @return callable
	 */
	'XmlContentCreator' => static function ( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'XmlContentCreator', XmlContentCreator::class );
		return new XmlContentCreator( $containerBuilder->create( 'ImporterServiceFactory' ) );
	},

	/**
	 * TextContentCreator
	 *
	 * @return callable
	 */
	'TextContentCreator' => static function ( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'TextContentCreator', TextContentCreator::class );

		$connectionManager = $containerBuilder->singleton( 'ConnectionManager' );

		$textContentCreator = new TextContentCreator(
			$containerBuilder->create( 'TitleFactory' ),
			$connectionManager->getConnection( 'mw.db' )
		);

		return $textContentCreator;
	},

	/**
	 * Importer
	 *
	 * @return callable
	 */
	'Importer' => static function ( $containerBuilder, ContentIterator $contentIterator ) {
		$containerBuilder->registerExpectedReturnType( 'Importer', Importer::class );

		$dispatchingContentCreator = new DispatchingContentCreator(
			[
				$containerBuilder->create( 'XmlContentCreator' ),
				$containerBuilder->create( 'TextContentCreator' )
			]
		);

		$importer = new Importer(
			$contentIterator,
			$dispatchingContentCreator
		);

		$importer->setReqVersion(
			$containerBuilder->singleton( 'Settings' )->get( 'smwgImportReqVersion' )
		);

		return $importer;
	},

	/**
	 * JsonContentIterator
	 *
	 * @return callable
	 */
	'JsonContentIterator' => static function ( $containerBuilder, $importFileDirs ) {
		$containerBuilder->registerExpectedReturnType( 'JsonContentIterator', JsonContentIterator::class );

		$jsonImportContentsFileDirReader = new JsonImportContentsFileDirReader(
			new ContentModeller(),
			new FileFetcher(),
			new File(),
			$importFileDirs
		);

		return new JsonContentIterator( $jsonImportContentsFileDirReader );
	},

];
