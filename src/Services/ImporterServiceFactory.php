<?php

namespace SMW\Services;

use ImportSource;
use ImportStreamSource;
use ImportStringSource;
use SMW\Importer\ContentIterator;
use SMW\Importer\Importer;
use SMW\Importer\JsonContentIterator;
use WikiImporter;

/**
 * @private
 *
 * This class provides service and factory functions for Import objects.
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ImporterServiceFactory {

	/**
	 * Indicates an Importer service file
	 */
	const SERVICE_FILE = 'importer.php';

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly ServicesContainer $servicesContainer ) {
	}

	/**
	 * Builds a `ServicesContainer` seeded with the Importer domain services
	 * defined in the `importer.php` wiring file.
	 *
	 * @since 7.0.0
	 */
	public static function newServicesContainer( string $servicesFileDir ): ServicesContainer {
		$servicesContainer = new ServicesContainer();

		$services = require $servicesFileDir . '/' . self::SERVICE_FILE;

		foreach ( $services as $key => $callback ) {
			$servicesContainer->add( $key, $callback );
		}

		return $servicesContainer;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $source
	 *
	 * @return ImportStringSource
	 */
	public function newImportStringSource( $source ) {
		return $this->servicesContainer->create( 'ImportStringSource', $this->servicesContainer, $source );
	}

	/**
	 * @since 3.0
	 *
	 * @param resource $source
	 *
	 * @return ImportStreamSource
	 */
	public function newImportStreamSource( $source ) {
		return $this->servicesContainer->create( 'ImportStreamSource', $this->servicesContainer, $source );
	}

	/**
	 * @since 3.0
	 *
	 * @param ImportSource $importSource
	 *
	 * @return WikiImporter
	 */
	public function newWikiImporter( ImportSource $importSource ) {
		return $this->servicesContainer->create( 'WikiImporter', $this->servicesContainer, $importSource );
	}

	/**
	 * @since 3.0
	 *
	 * @param ContentIterator $contentIterator
	 *
	 * @return Importer
	 */
	public function newImporter( ContentIterator $contentIterator ) {
		return $this->servicesContainer->create( 'Importer', $this->servicesContainer, $contentIterator );
	}

	/**
	 * @since 3.0
	 *
	 * @return JsonContentIterator
	 */
	public function newJsonContentIterator( $importFileDir ) {
		return $this->servicesContainer->create( 'JsonContentIterator', $this->servicesContainer, $importFileDir );
	}

}
