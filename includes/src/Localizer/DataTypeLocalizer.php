<?php

namespace SMW\Localizer;

use SMW\Cache\MessageCache;
use SMW\JsonFileReader;

use RuntimeException;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class DataTypeLocalizer {

	const FILE = 'datatype.spec.json';

	/** @var JsonFileReader */
	protected $fileReader = null;

	/** @var MessageCache */
	protected $messageCache = null;

	protected $useDefaultAliases = true;
	protected $datatypeLabels = array();
	protected $datatypeAliases = array();

	/**
	 * @since 1.9.3
	 *
	 * @param JsonFileReader $fileReader
	 * @param MessageCache $messageCache
	 */
	public function __construct( JsonFileReader $fileReader, MessageCache $messageCache ) {
		$this->fileReader = $fileReader;
		$this->messageCache = $messageCache;

		$this->messageCache->setCacheTimeOffset( $fileReader->getModificationTime() );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param ContextSource|null $context
	 *
	 * @return DataTypeLocalizer
	 */
	public static function newFromContext( \ContextSource $context = null ) {
		return new self(
			new JsonFileReader( __DIR__ . '/' . self::FILE ),
			MessageCache::ByContext( $context )
		);
	}

	/**
	 * @since 1.9.3
	 *
	 * @return DataTypeLocalizer
	 */
	public static function newFromContentLanguage() {
		return new self(
			new JsonFileReader( __DIR__ . '/' . self::FILE ),
			MessageCache::ByContentLanguage()
		);
	}

	/**
	 * @since 1.9.3
	 *
	 * @return array
	 */
	public function getDataTypeLabels() {

		if ( $this->datatypeLabels !== array() ) {
			return $this->datatypeLabels;
		}

		foreach ( $this->fileReader->getContents() as $content ) {

			$this->hasValidLabelContentOrThrowException( $content );

			$this->datatypeLabels[ $content[ 'id' ] ] = $this->messageCache->get( $content[ 'label' ] );
		}

		return $this->datatypeLabels;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return array
	 */
	public function getDataTypeAliases() {

		if ( $this->datatypeAliases !== array() ) {
			return $this->datatypeAliases;
		}

		foreach ( $this->fileReader->getContents() as $content ) {

			$this->hasValidAliasContentOrThrowException( $content );

			$this->assignIdToAlias( $content[ 'id' ], $this->messageCache->get( $content[ 'alias' ] ) );

			if ( $this->useDefaultAliases && isset( $content[ 'default' ] ) ) {
				$this->assignIdToAlias( $content[ 'id' ], $content[ 'default' ] );
			}
		}

		return $this->datatypeAliases;
	}

	protected function assignIdToAlias( $id, $contents ) {

		$contents = (array)$contents;

		foreach ( $contents as $content ) {
			$this->datatypeAliases[ $content ] = $id;
		}
	}

	private function hasValidAliasContentOrThrowException( $content ) {

		if ( is_array( $content ) && isset( $content[ 'alias' ] ) && isset( $content[ 'id' ] ) ) {
			return true;
		}

		throw new RuntimeException( "Invalid format, missing required alias and id key" );
	}

	private function hasValidLabelContentOrThrowException( $content ) {

		if ( is_array( $content ) && isset( $content[ 'label' ] ) && isset( $content[ 'id' ] ) ) {
			return true;
		}

		throw new RuntimeException( "Invalid format, missing required label and id key" );
	}

}
