<?php

namespace SMW\Services;

use ImportSource;
use ImportStreamSource;
use ImportStringSource;
use Onoi\CallbackContainer\CallbackContainerBuilder;
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
	 * @var CallbackContainerBuilder
	 */
	private $callbackContainerBuilder;

	/**
	 * @since 3.0
	 */
	public function __construct( CallbackContainerBuilder $callbackContainerBuilder ) {
		$this->callbackContainerBuilder = $callbackContainerBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $source
	 *
	 * @return ImportStringSource
	 */
	public function newImportStringSource( $source ) {
		return $this->callbackContainerBuilder->create( 'ImportStringSource', $source );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $source
	 *
	 * @return ImportStreamSource
	 */
	public function newImportStreamSource( $source ) {
		return $this->callbackContainerBuilder->create( 'ImportStreamSource', $source );
	}

	/**
	 * @since 3.0
	 *
	 * @param ImportSource $importSource
	 *
	 * @return WikiImporter
	 */
	public function newWikiImporter( ImportSource $importSource ) {
		return $this->callbackContainerBuilder->create( 'WikiImporter', $importSource );
	}

	/**
	 * @since 3.0
	 *
	 * @param ContentIterator $contentIterator
	 *
	 * @return Importer
	 */
	public function newImporter( ContentIterator $contentIterator ) {
		return $this->callbackContainerBuilder->create( 'Importer', $contentIterator );
	}

	/**
	 * @since 3.0
	 *
	 * @return JsonContentIterator
	 */
	public function newJsonContentIterator( $importFileDir ) {
		return $this->callbackContainerBuilder->create( 'JsonContentIterator', $importFileDir );
	}

}
