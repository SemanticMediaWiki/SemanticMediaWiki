<?php

namespace SMW\MediaWiki\Content;

use SMW\Schema\SchemaFactory;
use SMW\Schema\Exception\SchemaTypeNotFoundException;
use SMW\Exception\JSONParseException;
use SMW\Schema\Schema;
use SMW\ParserData;
use SMW\Message;
use SMW\Services\ServicesFactory as ApplicationFactory;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use JsonContent;
use Title;
use User;
use ParserOptions;
use ParserOutput;
use WikiPage;
use Status;

/**
 * The content model supports both JSON and YAML (as a superset of JSON), allowing
 * for its content to be represented in JSON when required while a user may choose
 * YAML to edit/store the native content (due to improve readability or
 * aid others with additional inline comments).
 *
 * Comments (among other elements) will not be represented in JSON output when
 * requested by the `Content::toJson` method.
 *
 * @see https://en.wikipedia.org/wiki/YAML#Comparison_with_JSON
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContent extends SchemaContent38 {

	/**
	 * @var SchemaFactory
	 */
	private $schemaFactory;

	/**
	 * @var SchemaContentFormatter
	 */
	private $contentFormatter;

	/**
	 * @var string
	 */
	private $errorMsg = '';

	private function initServices() {

		if ( $this->schemaFactory === null ) {
			$this->schemaFactory = new SchemaFactory();
		}

		if ( $this->contentFormatter === null ) {
			$this->contentFormatter = new SchemaContentFormatter(
				ApplicationFactory::getInstance()->getStore()
			);
		}
	}

	/**
	 * @see Content::prepareSave
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function prepareSave( WikiPage $page, $flags, $parentRevId, User $user ) {

		$this->initServices();
		$title = $page->getTitle();

		$this->setTitlePrefix( $title );

		$errors = [];
		$schema = null;

		try {
			$schema = $this->schemaFactory->newSchema(
				$title->getDBKey(),
				$this->toJson()
			);
		} catch ( SchemaTypeNotFoundException $e ) {
			if ( !$this->isValid && $this->errorMsg !== '' ) {
				$errors[] = [ 'smw-schema-error-json', $this->errorMsg ];
			} elseif ( $e->getType() === '' || $e->getType() === null ) {
				$errors[] = [ 'smw-schema-error-type-missing' ];
			} else {
				$errors[] = [ 'smw-schema-error-type-unknown', $e->getType() ];
			}
		}

		if ( $schema !== null ) {
			$errors = $this->schemaFactory->newSchemaValidator()->validate(
				$schema
			);

			$schema_link = pathinfo(
				$schema->info( Schema::SCHEMA_VALIDATION_FILE ),
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
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function fillParserOutput( Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output ) {

		if ( !$generateHtml || !$this->isValid() ) {
			return;
		}

		$this->initServices();

		$output->addModuleStyles(
			$this->contentFormatter->getModuleStyles()
		);

		$output->addModules(
			$this->contentFormatter->getModules()
		);

		$parserData = new ParserData( $title, $output );
		$schema = null;

		$this->contentFormatter->isYaml(
			$this->isYaml
		);

		$this->setTitlePrefix( $title );

		try {
			$schema = $this->schemaFactory->newSchema(
				$title->getDBKey(),
				$this->toJson()
			);
		} catch ( SchemaTypeNotFoundException $e ) {

			$this->contentFormatter->setUnknownType(
				$e->getType()
			);

			$output->setText(
				$this->contentFormatter->getText( $this->mText )
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
			$this->contentFormatter->getHelpLink( $schema )
		);

		$errors = $this->schemaFactory->newSchemaValidator()->validate(
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

		$this->contentFormatter->setType(
			$this->schemaFactory->getType( $schema->get( 'type' ) )
		);

		$output->setText(
			$this->contentFormatter->getText( $this->mText, $schema, $errors )
		);

		$parserData->copyToParserOutput();
	}

	private function setTitlePrefix( Title $title ) {

		if ( $this->parse === null ) {
			$this->decodeJSONContent();
		}

		// The decode could return with a JSON syntax error therefore
		// double check the parse state before trying to continue
		if ( !is_object( $this->parse ) ) {
			return;
		}

		$schemaName = $title->getDBKey();
		$title_prefix = '';

		if ( strpos( $schemaName, ':' ) !== false ) {
			list( $title_prefix, ) = explode( ':',  $schemaName );
		}

		// Allow to use the schema validation against a possible
		// required naming convention (aka title prefix)
		$this->parse->title_prefix = $title_prefix;
	}

}
