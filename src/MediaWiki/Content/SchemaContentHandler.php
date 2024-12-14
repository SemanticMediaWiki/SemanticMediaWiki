<?php

namespace SMW\MediaWiki\Content;

use Content;
use JsonContentHandler;
use ParserOutput;
use Status;
use Title;
use WikiPage;
use MediaWiki\Content\ValidationParams;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\Transform\PreSaveTransformParams;
use MediaWiki\MediaWikiServices;
use SMW\ParserData;
use SMW\Schema\Exception\SchemaTypeNotFoundException;
use SMW\Schema\Schema;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentHandler extends JsonContentHandler {

	public function __construct() {
		parent::__construct( CONTENT_MODEL_SMW_SCHEMA, [ CONTENT_FORMAT_JSON ] );
	}

	/**
	 * Returns true, because wikitext supports caching using the
	 * ParserCache mechanism.
	 *
	 * @since 1.21
	 *
	 * @return bool Always true.
	 *
	 * @see ContentHandler::isParserCacheSupported
	 */
	public function isParserCacheSupported() {
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	protected function getContentClass() {
		return SchemaContent::class;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsSections() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsCategories() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsRedirects() {
		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 */
	public function preSaveTransform( Content $content, PreSaveTransformParams $pstParams ): Content {
		return $content->preSaveTransform(
			$pstParams->getPage(),
			$pstParams->getUser(),
			$pstParams->getParserOptions()
		);
	}

	/**
	 * @see ContentHandler::validateSave
	 * @since 5.0
	 *
	 * {@inheritDoc}
	 */
	public function validateSave( Content $content, ValidationParams $validationParams ) {
		$content->initServices();

		$page = $validationParams->getPageIdentity();

		if ( !( $page instanceof WikiPage ) ) {
			$services = MediaWikiServices::getInstance();
			$wikiPageFactory = $services->getWikiPageFactory();
			$page = $wikiPageFactory->newFromTitle( $page );
		}

		$title = $page->getTitle();

		$content->setTitlePrefix( $title );

		$errors = [];
		$schema = null;

		try {
			$schema = $content->getSchemaFactory()->newSchema(
				$title->getDBKey(),
				$content->toJson()
			);
		} catch ( SchemaTypeNotFoundException $e ) {
			if ( !$content->isValid() && $content->getErrorMsg() !== '' ) {
				$errors[] = [ 'smw-schema-error-json', $content->getErrorMsg() ];
			} elseif ( $e->getType() === '' || $e->getType() === null ) {
				$errors[] = [ 'smw-schema-error-type-missing' ];
			} else {
				$errors[] = [ 'smw-schema-error-type-unknown', $e->getType() ];
			}
		}

		if ( $schema !== null ) {
			$errors = $content->getSchemaFactory()->newSchemaValidator()->validate(
				$schema
			);

			$schema_link = pathinfo(
				$schema->info( Schema::SCHEMA_VALIDATION_FILE ) ?? '',
				PATHINFO_FILENAME
			);
		}

		$status = Status::newGood();

		if ( $errors !== [] && $schema === null ) {
			array_unshift( $errors, [ 'smw-schema-error-input' ] );
		} elseif ( $errors !== [] ) {
			array_unshift( $errors, [ 'smw-schema-error-input-schema', $schema_link ] );
		}

		foreach ( $errors as $error ) {

			if ( isset( $error['property'] ) && $error['property'] === 'title_prefix' ) {

				if ( isset( $error['enum'] ) ) {
					$group = end( $error['enum'] );
				} elseif ( isset( $error['const'] ) ) {
					$group = $error['const'];
				} else {
					continue;
				}

				$error = [ 'smw-schema-error-title-prefix', "$group:" ];
			}

			if ( isset( $error['message'] ) ) {
				$status->fatal( 'smw-schema-error-violation', $error['property'], $error['message'] );
			} elseif ( is_string( $error ) ) {
				$status->fatal( $error );
			} else {
				$status->fatal( ...$error );
			}
		}

		return $status;
	}

	/**
	 *
	 * {@inheritDoc}
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	) {
		$title = Title::castFromPageReference( $cpoParams->getPage() );

		if ( !$cpoParams->getGenerateHtml() || !$content->isValid() ) {
			return;
		}

		$content->initServices();
		$contentFormatter = $content->getContentFormatter();
		$schemaFactory = $content->getSchemaFactory();

		$output->addModuleStyles(
			$contentFormatter->getModuleStyles()
		);

		$output->addModules(
			$contentFormatter->getModules()
		);

		$parserData = new ParserData( $title, $output );
		$schema = null;

		$contentFormatter->isYaml(
			$content->isYaml()
		);

		$content->setTitlePrefix( $title );

		try {
			$schema = $schemaFactory->newSchema(
				$title->getDBKey(),
				$content->toJson()
			);
		} catch ( SchemaTypeNotFoundException $e ) {

			$contentFormatter->setUnknownType(
				$e->getType()
			);

			$output->setText(
				$contentFormatter->getText( $content->getText() )
			);

			$parserData->addError(
				[ [ 'smw-schema-error-type-unknown', $e->getType() ] ]
			);

			$parserData->copyToParserOutput();
		}

		if ( $schema === null ) {
			return;
		}

		$output->setIndicator(
			'mw-helplink',
			$contentFormatter->getHelpLink( $schema )
		);

		$errors = $schemaFactory->newSchemaValidator()->validate(
			$schema
		);

		foreach ( $errors as $error ) {
			if ( isset( $error['property'] ) && isset( $error['message'] ) ) {

				if ( $error['property'] === 'title_prefix' ) {
					if ( isset( $error['enum'] ) ) {
						$group = end( $error['enum'] );
					} elseif ( isset( $error['const'] ) ) {
						$group = $error['const'];
					} else {
						continue;
					}

					$error['message'] = Message::get( [ 'smw-schema-error-title-prefix', $group ] );
				}

				$parserData->addError(
					[ [ 'smw-schema-error-violation', $error['property'], $error['message'] ] ]
				);
			} else {
				$parserData->addError( (array)$error );
			}
		}

		$contentFormatter->setType(
			$schemaFactory->getType( $schema->get( 'type' ) )
		);

		$output->setText(
			$contentFormatter->getText( $content->getText(), $schema, $errors )
		);

		$parserData->copyToParserOutput();
	}
}
