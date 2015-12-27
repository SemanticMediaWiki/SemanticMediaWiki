<?php

namespace SMW;

use SMW\MediaWiki\MediaWikiNsContentReader;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SchemaManager {

	/**
	 * @var SchemaManager
	 */
	private static $instance = null;

	/**
	 * @var SchemaReader
	 */
	private $schemaReader = null;

	/**
	 * @var array
	 */
	private $bySchemaCache = array();

	/**
	 * @var array
	 */
	private $schemas = array();

	/**
	 * @since 2.4
	 *
	 * @param SchemaReader $schemaReader
	 */
	public function __construct( SchemaReader $schemaReader ) {
		$this->schemaReader = $schemaReader;
		$this->registerSchema( 'smw-schema-definition' );
	}

	/**
	 * @since 2.4
	 *
	 * @return SchemaManager
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self( new SchemaReader( new MediaWikiNsContentReader() ) );
		}

		return self::$instance;
	}

	/**
	 * @since 2.4
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.4
	 *
	 * @return schemaReader
	 */
	public function getSchemaReader() {
		return $this->schemaReader;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $name
	 */
	public function registerSchema( $name ) {
		$this->schemas[] = ucfirst( $name );
		$this->schemaReader->registerSchema( $name );
	}

	/**
	 * @since 2.4
	 *
	 * @param Title|null $title
	 * @param integer &$model
	 *
	 * @return boolean
	 */
	public function modifyContentHandlerDefaultModelFor( Title $title, &$model ) {

		// CONTENT_MODEL_JSON is only specified in 1.24+
		if ( !$this->isSchemaPage( $title ) || !defined( 'CONTENT_MODEL_JSON' ) ) {
			return true;
		}

		$model = CONTENT_MODEL_JSON;
		return false;
	}

	/**
	 * @since 2.4
	 *
	 * @param Title|null $title
	 *
	 * @return boolean
	 */
	public function getMessageForCategoryPage( Title $title ) {

		$result = '';

		if ( !$this->canEdit( $title, $result ) ) {
			return wfMessage( 'smw-schema-category-notice', $title->getText() )->parse();
		}

		return '';
	}

	/**
	 * @since 2.4
	 *
	 * @param Title|null $title
	 *
	 * @return boolean
	 */
	public function isSchemaPage( Title $title = null ) {
		return $title !== null && $title->getNamespace() === NS_MEDIAWIKI && in_array( $title->getDBKey(), $this->schemas );
	}

	/**
	 * @since 2.4
	 *
	 * @param Title $title
	 * @param string &$result
	 *
	 * @return boolean
	 */
	public function canEdit( Title $title, &$result ) {

		if ( $title->getNamespace() !== SMW_NS_PROPERTY && $title->getNamespace() !== NS_CATEGORY ) {
			return true;
		}

		$name = $title->getText();

		if ( isset( $this->bySchemaCache[$name] ) ) {
			return $result = false;
		}

		if (
			( $title->getNamespace() === SMW_NS_PROPERTY && $this->isPropertyBySchema( $name ) ) ||
			( $title->getNamespace() === NS_CATEGORY && $this->isCategoryBySchema( $name ) ) ) {
			return $result = false;
		}

		return true;
	}

	/**
	 * @since 2.4
	 *
	 * @param Title $title
	 * @param string &$result
	 *
	 * @return boolean
	 */
	public function canDelete( Title $title, &$result ) {

		if ( $title->getNamespace() !== SMW_NS_PROPERTY && $title->getNamespace() !== NS_CATEGORY ) {
			return true;
		}

		$name = $title->getText();

		if ( isset( $this->bySchemaCache[$name] ) ) {
			return $result = false;
		}

		if (
			( $title->getNamespace() === SMW_NS_PROPERTY && $this->isPropertyBySchema( $name ) ) ||
			( $title->getNamespace() === NS_CATEGORY && $this->isCategoryBySchema( $name ) ) ) {
			return $result = false;
		}

		return $result = DIProperty::newFromUserLabel( $name )->isUserDefined();
	}

	/**
	 * @since 2.4
	 *
	 * @param Title $title
	 * @param string &$isMovable
	 *
	 * @return boolean
	 */
	public function canMove( Title $title, &$isMovable ) {

		if ( $title->getNamespace() !== SMW_NS_PROPERTY && $title->getNamespace() !== NS_CATEGORY ) {
			return true;
		}

		$name = $title->getText();

		if ( isset( $this->bySchemaCache[$name] ) ) {
			$isMovable = false;
			return true;
		}

		if (
			( $title->getNamespace() === SMW_NS_PROPERTY && $this->isPropertyBySchema( $title->getText() ) ) ||
			( $title->getNamespace() === NS_CATEGORY && $this->isCategoryBySchema( $title->getText() ) ) ) {
			$isMovable = false;
			return true;
		}

		$isMovable = DIProperty::newFromUserLabel( $name )->isUserDefined();

		return true;
	}

	private function isPropertyBySchema( $propertyToCheck ) {
		$properties = $this->schemaReader->read( 'properties' );

		foreach ( $properties as $property ) {
			if ( isset( $property['property'] ) && $property['property'] === $propertyToCheck ) {
				return $this->bySchemaCache[$propertyToCheck] = true;
			}
		}

		return false;
	}

	private function isCategoryBySchema( $categoryToCheck ) {
		$categories = $this->schemaReader->read( 'categories' );

		foreach ( $categories as $category ) {
			if ( isset( $category['category'] ) && $category['category'] === $categoryToCheck ) {
				return $this->bySchemaCache[$categoryToCheck] = true;
			}
		}

		return false;
	}

}
