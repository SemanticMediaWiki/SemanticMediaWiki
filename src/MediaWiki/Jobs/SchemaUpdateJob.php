<?php

namespace SMW\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\SchemaManager;
use Title;
use WikiPage;
use Hooks;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SchemaUpdateJob extends JobBase {

	/**
	 * @var SchemaManager
	 */
	private $schemaManager;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @since 2.4
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\SchemaUpdateJob', $title, $params );
		$this->schemaManager = SchemaManager::getInstance();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @see Job::run
	 *
	 * @since 2.4
	 */
	public function run() {

		// Read from the DB and avoid oudated references from the MessageCache
		// when used in connection with the NS_MEDIAWIKI namespace
		$this->schemaManager->getSchemaReader()->skipMessageCache();

		$this->mapPropertySchemaFor(
			$this->schemaManager->getSchemaReader()->read( 'properties' )
		);

		$this->mapCategorySchemaFor(
			$this->schemaManager->getSchemaReader()->read( 'categories' )
		);

		return true;
	}

	/**
	 * @since 2.4
	 *
	 * @param array $properties
	 */
	public function mapPropertySchemaFor( array $properties ) {

		$user = \User::newFromName( 'SchemaUpdateJob' );

		foreach ( $properties as $property ) {

			if ( !isset( $property['property'] ) ) {
				continue;
			}

			$title = Title::makeTitleSafe(
				SMW_NS_PROPERTY,
				$property['property']
			);

			$page = $this->applicationFactory->newPageCreator()->createPage( $title );

			$this->addContentTo(
				$page,
				$user,
				$this->doMapPropertySchemaToContentFor( $property )
			);
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param array $categories
	 */
	public function mapCategorySchemaFor( array $categories ) {

		$user = \User::newFromName( 'SchemaUpdateJob' );

		foreach ( $categories as $category ) {

			if ( !isset( $category['category'] ) ) {
				continue;
			}

			$title = Title::makeTitleSafe(
				NS_CATEGORY,
				$category['category']
			);

			$page = $this->applicationFactory->newPageCreator()->createPage( $title );

			$this->addContentTo(
				$page,
				$user,
				$this->doMapCategorySchemaToContentFor( $category )
			);
		}
	}

	private function addContentTo( $page, $user, $content ) {

		Hooks::run( 'SMW::Job::BeforeEntitySchemaUpdateComplete', array( $page, &$content ) );
		$hash = '';

		if ( $page->getTitle()->exists() ) {
			$hash = md5( $this->getContent( $page ) );
		}

		if ( $hash === md5( $content ) ) {
			return;
		}

		$this->doEditOnTransactionIdle(
			$page,
			$user,
			$content,
			'Update provided by the schema definition'
		);
	}

	private function doMapPropertySchemaToContentFor( array $property ) {

		$content = '';

		$map = array(
			'type'          => 'Has type',
			'units'         => 'Display units',
			'importedfrom'  => 'Imported from',
			'subpropertyof' => 'Subproperty of',
			'correspondsto' => 'Corresponds to',
			'constraintof'  => 'Allows value',
			'allowsvalue'   => 'Allows value',
			'fields'        => 'Has fields',
		);

		foreach ( $property as $key => $value ) {

			$key = strtolower( trim( $key ) );

			if ( $key === 'redirectto' ) {
				$content = $this->addRedirectTo( $value );
				break;
			}

			if ( $key === 'categories' ) {
				$content .= $this->addCategoriesToContent( $value );
				continue;
			}

			if ( !isset( $map[$key] ) ) {
				continue;
			}

			$content .= $this->addAnnotationToContent( $map[$key], (array)$value );
		}

		return $content;
	}

	private function doMapCategorySchemaToContentFor( array $category ) {

		$content = '';

		$map = array(
			'importedfrom' => 'Imported from'
		);

		foreach ( $category as $key => $value ) {

			$key = strtolower( trim( $key ) );

			if ( $key === 'subcategoryof' ) {
				$content .= $this->addCategoriesToContent( $value );
				continue;
			}

			if ( !isset( $map[$key] ) ) {
				continue;
			}

			$content .= $this->addAnnotationToContent( $map[$key], (array)$value );
		}

		return $content;
	}

	private function addRedirectTo( $target ) {
		return '#REDIRECT [[' . 'Property' . ':' . $target . ']]';
	}

	private function addAnnotationToContent( $key, $value ) {

		$content = '';
		$label = '';

		foreach ( $value as $val ) {
			$content .= "* [[$key::$val]] " . "\n";
		}

		return $content;
	}

	private function addCategoriesToContent( array $categories ) {
		$content = '';

		foreach ( $categories as $category ) {
			$content .= '[[' . 'Category' . ':' . $category . ']]';
		}

		return "\n" . $content;
	}

	private function getContent( $page ) {

		// MW 1.19 / 1.22
		if ( !method_exists( $page, 'getContent' ) ) {
			return $page->getText();
		}

		$content = $page->getContent();

		if ( $content instanceof TextContent ) {
			return $content->getNativeData();
		}

		return '';
	}

	private function doEditOnTransactionIdle( $page, $user, $pageContent = '', $editMessage = '' ) {

		// MW 1.19 / 1.22
		if ( !class_exists( 'WikitextContent' ) ) {
			return $this->getConnection()->onTransactionIdle( function () use ( $page, $user, $pageContent, $editMessage ) {
				$page->doEdit( $pageContent, $editMessage, EDIT_FORCE_BOT, false, $user );
			} );
		}

		$this->getConnection()->onTransactionIdle( function () use ( $page, $user, $pageContent, $editMessage ) {

			$content = new \WikitextContent( $pageContent );

			$page->doEditContent(
				$content,
				$editMessage,
				EDIT_FORCE_BOT,
				false,
				$user
			);
		} );
	}

	private function getConnection() {

		if ( $this->connection === null ) {
			$this->connection = $this->applicationFactory->newMwCollaboratorFactory()->newMediaWikiDatabaseConnectionProvider()->getConnection();
		}

		return $this->connection;
	}

}
