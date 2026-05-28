<?php

namespace SMW\Services;

use ImportSource;
use ImportStreamSource;
use ImportStringSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
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
use WikiImporter;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed via ImporterServiceFactory.
 *
 * Each callback receives the Importer domain `ServicesContainer` so it can resolve
 * sibling importer services. Global SMW services are resolved through
 * `ServicesFactory::getInstance()`.
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
return [

	/**
	 * @return ImportStringSource
	 */
	'ImportStringSource' => static function ( ServicesContainer $container, string $source ): ImportStringSource {
		return new ImportStringSource( $source );
	},

	/**
	 * @param resource $source
	 *
	 * @return ImportStreamSource
	 */
	'ImportStreamSource' => static function ( ServicesContainer $container, $source ): ImportStreamSource {
		return new ImportStreamSource( $source );
	},

	/**
	 * @return WikiImporter
	 */
	'WikiImporter' => static function ( ServicesContainer $container, ImportSource $importSource ): WikiImporter {
		return MediaWikiServices::getInstance()->getWikiImporterFactory()->getWikiImporter(
			$importSource,
			RequestContext::getMain()->getAuthority()
		);
	},

	/**
	 * @return XmlContentCreator
	 */
	'XmlContentCreator' => static function ( ServicesContainer $container ): XmlContentCreator {
		return new XmlContentCreator( new ImporterServiceFactory( $container ) );
	},

	/**
	 * @return TextContentCreator
	 */
	'TextContentCreator' => static function ( ServicesContainer $container ): TextContentCreator {
		$servicesFactory = ServicesFactory::getInstance();
		$connectionManager = $servicesFactory->getConnectionManager();

		$mwServices = MediaWikiServices::getInstance();

		return new TextContentCreator(
			$mwServices->getTitleFactory(),
			$connectionManager->getConnection( 'mw.db' ),
			$mwServices->getWikiPageFactory()
		);
	},

	/**
	 * @return Importer
	 */
	'Importer' => static function ( ServicesContainer $container, ContentIterator $contentIterator ): Importer {
		$dispatchingContentCreator = new DispatchingContentCreator(
			[
				$container->create( 'XmlContentCreator', $container ),
				$container->create( 'TextContentCreator', $container )
			]
		);

		$importer = new Importer(
			$contentIterator,
			$dispatchingContentCreator
		);

		$importer->setReqVersion(
			ServicesFactory::getInstance()->getSettings()->get( 'smwgImportReqVersion' )
		);

		return $importer;
	},

	/**
	 * @return JsonContentIterator
	 */
	'JsonContentIterator' => static function ( ServicesContainer $container, $importFileDirs ): JsonContentIterator {
		$jsonImportContentsFileDirReader = new JsonImportContentsFileDirReader(
			new ContentModeller(),
			new FileFetcher(),
			new File(),
			$importFileDirs
		);

		return new JsonContentIterator( $jsonImportContentsFileDirReader );
	},

];
