<?php

namespace SMW\MediaWiki\Content;

use SMW\Schema\SchemaFactory;
use SMW\Schema\Exception\SchemaTypeNotFoundException;
use SMW\Schema\Schema;
use SMW\ParserData;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use JsonContent;
use Title;
use User;
use ParserOptions;
use ParserOutput;
use Html;

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
class SchemaContent extends JsonContent {

	/**
	 * @var SchemaFactory
	 */
	private $schemaFactory;

	/**
	 * @var ContentFormatter
	 */
	private $contentFormatter;

	/**
	 * @var array
	 */
	private $parse;

	/**
	 * @var boolean
	 */
	private $isYaml = false;

	/**
	 * @var boolean
	 */
	private $isValid;

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function __construct( $text ) {
		parent::__construct( $text, CONTENT_MODEL_SMW_SCHEMA );
	}

	/**
	 * `Content::getNativeData` will return the "native" text representation which
	 * in case of YAML is just the text and not a JSON string. Therefore
	 * `getNativeData` preserves the original user input.
	 *
	 * Instead, use this method to retrieve a JSON compatible string for both
	 * JSON and YAML for when the data is valid.
	 *
	 * @since 3.0
	 *
	 * @return null|string
	 */
	public function toJson() {

		if ( $this->isValid() ) {
			return json_encode( $this->parse );
		}

		return null;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean
	 */
	public function isYaml() {

		if ( $this->isValid() ) {
			return $this->isYaml;
		}

		return false;
	}

	/**
	 * @see Content::isValid
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isValid() {

		if ( $this->isValid === null ) {
			$this->decode_content();
		}

		return $this->isValid;
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
				$this->contentFormatter->getText( $this->mText, $this->isYaml )
			);

			$parserData->addError(
				[ [ 'smw-schema-error-type-unknown', $e->getType() ] ]
			);

			$parserData->copyToParserOutput();
		}

		if ( $schema === null ) {
			return ;
		}

		$output->setIndicator(
			'mw-helplink',
			$this->contentFormatter->getHelpLink( $schema )
		);

		$errors = $this->schemaFactory->newSchemaValidator()->validate(
			$schema
		);

		$this->contentFormatter->setType(
			$this->schemaFactory->getType( $schema->get( 'type' ) )
		);

		$output->setText(
			$this->contentFormatter->getText( $this->mText, $this->isYaml, $schema, $errors )
		);

		foreach ( $errors as $error ) {
			if ( isset( $error['property'] ) && isset( $error['message'] ) ) {
				$parserData->addError(
					[ ['smw-schema-error-violation', $error['property'], $error['message'] ] ]
				);
			}
		}

		$parserData->copyToParserOutput();
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function preSaveTransform( Title $title, User $user, ParserOptions $popts ) {
		// FIXME: WikiPage::doEditContent invokes PST before validation. As such, native data
		// may be invalid (though PST result is discarded later in that case).
		if ( !$this->isValid() ) {
			return $this;
		}

		if ( !$this->isYaml ) {
			$text = self::normalizeLineEndings(
				json_encode( $this->parse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			);
		} else {
			$text = self::normalizeLineEndings( $this->mText );
		}

		return new static( $text );
	}

	/**
	 * @since 3.0
	 *
	 * @param SchemaFactory $schemaFactory
	 * @param ContentFormatter $contentFormatter
	 */
	public function setServices( SchemaFactory $schemaFactory, SchemaContentFormatter $contentFormatter ) {
		$this->schemaFactory = $schemaFactory;
		$this->contentFormatter = $contentFormatter;
	}

	/**
	 * @see TextContent::normalizeLineEndings (MW 1.28+)
	 *
	 * @param $text
	 * @return string
	 */
	public static function normalizeLineEndings( $text ) {
		return str_replace( [ "\r\n", "\r" ], "\n", rtrim( $text ) );
	}

	private function initServices() {

		if ( $this->schemaFactory === null ) {
			$this->schemaFactory = new SchemaFactory();
		}

		if ( $this->contentFormatter === null ) {
			$this->contentFormatter = new SchemaContentFormatter();
		}
	}

	private function decode_content() {

		// Support either JSON or YAML, if the class is available! Do a quick
		// check on `{ ... }` to decide whether it is a non-JSON string.
		if ( $this->mText !== '' && $this->mText[0] !== '{' && substr( $this->mText, -1 ) !== '}' && class_exists( '\Symfony\Component\Yaml\Yaml' ) ) {

			try {
				$this->parse = Yaml::parse( $this->mText );
				$this->isYaml = true;
			} catch ( ParseException $e ) {
				$this->isYaml = false;
				$this->parse = null;
			}

			return $this->isValid = $this->isYaml;
		} elseif ( $this->mText !== '' ) {

			// Note that this parses it without casting objects to associative arrays.
			// Objects and arrays are kept as distinguishable types in the PHP values.
			$this->parse = json_decode( $this->mText );
			$this->isValid = json_last_error() === JSON_ERROR_NONE;

			return $this->isValid;
		}
	}

}
