<?php

namespace SMW\Services;

use ImportSource;
use Onoi\CallbackContainer\ContainerBuilder;
use SMW\Importer\ContentIterator;

/**
 * @private
 *
 * This class provides service and factory functions for Import objects.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ImporterServiceFactory {

	/**
	 * @var ContainerBuilder
	 */
	private $containerBuilder;

	/**
	 * @since 3.0
	 */
	public function __construct( ContainerBuilder $containerBuilder ) {
		$this->containerBuilder = $containerBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $source
	 *
	 * @return ImportStringSource
	 */
	public function newImportStringSource( $source ) {
		return $this->containerBuilder->create( 'ImportStringSource', $source );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $source
	 *
	 * @return ImportStreamSource
	 */
	public function newImportStreamSource( $source ) {
		return $this->containerBuilder->create( 'ImportStreamSource', $source );
	}

	/**
	 * @since 3.0
	 *
	 * @param ImportSource $importSource
	 *
	 * @return WikiImporter
	 */
	public function newWikiImporter( ImportSource $importSource ) {
		return $this->containerBuilder->create( 'WikiImporter', $importSource );
	}

	/**
	 * @since 3.0
	 *
	 * @param ContentIterator $contentIterator
	 *
	 * @return Importer
	 */
	public function newImporter( ContentIterator $contentIterator ) {
		return $this->containerBuilder->create( 'Importer', $contentIterator );
	}

	/**
	 * @since 3.0
	 *
	 * @return JsonContentIterator
	 */
	public function newJsonContentIterator( $importFileDir ) {
		return $this->containerBuilder->create( 'JsonContentIterator', $importFileDir );
	}

}
