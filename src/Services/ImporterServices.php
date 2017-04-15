<?php

namespace SMW\Services;

use SMW\Importer\Importer;
use SMW\Importer\ContentIterator;
use SMW\Importer\JsonContentIterator;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Importer\ContentCreators\DispatchingContentCreator;
use SMW\Importer\ContentCreators\XmlContentCreator;
use SMW\Importer\ContentCreators\TextContentCreator;

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
	 * ImporterServiceFactory
	 *
	 * @return callable
	 */
	'ImporterServiceFactory' => function( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'ImporterServiceFactory', '\SMW\Services\ImporterServiceFactory' );
		return new ImporterServiceFactory( $containerBuilder );
	},

	/**
	 * XmlContentCreator
	 *
	 * @return callable
	 */
	'XmlContentCreator' => function( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'XmlContentCreator', '\SMW\Importer\ContentCreators\XmlContentCreator' );
		return new XmlContentCreator( $containerBuilder->create( 'ImporterServiceFactory' ) );
	},

	/**
	 * TextContentCreator
	 *
	 * @return callable
	 */
	'TextContentCreator' => function( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'TextContentCreator', '\SMW\Importer\ContentCreators\TextContentCreator' );

		$textContentCreator = new TextContentCreator(
			$containerBuilder->create( 'PageCreator' ),
			$containerBuilder->create( 'DatabaseConnectionProvider' )->getConnection()
		);

		return $textContentCreator;
	},

	/**
	 * Importer
	 *
	 * @return callable
	 */
	'Importer' => function( $containerBuilder, ContentIterator $contentIterator  ) {
		$containerBuilder->registerExpectedReturnType( 'Importer', '\SMW\Importer\Importer' );

		$dispatchingContentCreator = new DispatchingContentCreator(
			array(
				$containerBuilder->create( 'XmlContentCreator' ),
				$containerBuilder->create( 'TextContentCreator' )
			)
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
	'JsonContentIterator' => function( $containerBuilder, $importFileDir ) {
		$containerBuilder->registerExpectedReturnType( 'JsonContentIterator', '\SMW\Importer\JsonContentIterator' );

		$jsonImportContentsFileDirReader = new JsonImportContentsFileDirReader(
			$importFileDir
		);

		return new JsonContentIterator( $jsonImportContentsFileDirReader );
	},

);