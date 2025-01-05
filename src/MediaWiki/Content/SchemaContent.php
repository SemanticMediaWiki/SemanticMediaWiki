<?php

namespace SMW\MediaWiki\Content;

use JsonContent;
use ParserOptions;
use SMW\Exception\JSONParseException;
use SMW\Schema\SchemaFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Title;
use User;

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
 * @license GPL-2.0-or-later
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
	 * @var SchemaContentFormatter
	 */
	private $contentFormatter;

	/**
	 * @var array
	 */
	private $parse;

	/**
	 * @var bool
	 */
	private $isYaml = false;

	/**
	 * @var bool
	 */
	private $isValid;

	/**
	 * @var string
	 */
	private $errorMsg = '';

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function __construct( $text ) {
		parent::__construct( $text, CONTENT_MODEL_SMW_SCHEMA );
	}

	/**
	 * This class doesn't own the properties but needs to guard against
	 * a possible serialization attempt. (@see #4210)
	 *
	 * @since 3.1
	 *
	 * @return array
	 */
	public function __sleep() {
		return [ 'model_id', 'mText' ];
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
			$this->decodeJSONContent();
		}

		return $this->isValid;
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
	 * @param SchemaContentFormatter|null $contentFormatter
	 */
	public function setServices( SchemaFactory $schemaFactory, ?SchemaContentFormatter $contentFormatter = null ) {
		$this->schemaFactory = $schemaFactory;
		$this->contentFormatter = $contentFormatter;
	}

	/**
	 * @see TextContent::normalizeLineEndings (MW 1.28+)
	 *
	 * @param $text
	 *
	 * @return string
	 */
	public static function normalizeLineEndings( $text ) {
		return str_replace( [ "\r\n", "\r" ], "\n", rtrim( $text ) );
	}

	public function initServices() {
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
	 * Gets the content formatter.
	 *
	 * @return SchemaContentFormatter|null The content formatter instance or null if not set.
	 */
	public function getContentFormatter() {
		return $this->contentFormatter;
	}

	/**
	 * Gets the schema factory.
	 *
	 * @return SchemaFactory The schema factory instance.
	 */
	public function getSchemaFactory() {
		return $this->schemaFactory;
	}

	private function decodeJSONContent() {
		// Support either JSON or YAML, if the class is available! Do a quick
		// check on `{ ... }` to decide whether it is a non-JSON string.
		if (
			$this->mText !== '' &&
			$this->mText[0] !== '{' &&
			substr( $this->mText, -1 ) !== '}' &&
			class_exists( '\Symfony\Component\Yaml\Yaml' ) ) {

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

			if ( $this->isValid ) {
				return true;
			}

			$jsonParseException = new JSONParseException(
				$this->mText
			);

			$this->errorMsg = $jsonParseException->getTidyMessage();

			return false;
		}
	}

	public function setTitlePrefix( Title $title ) {
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
			[ $title_prefix, ] = explode( ':', $schemaName );
		}

		// Allow to use the schema validation against a possible
		// required naming convention (aka title prefix)
		// TODO: Illegal dynamic property (#5421)
		$this->parse->title_prefix = $title_prefix;
	}

	public function getErrorMsg() {
		return $this->errorMsg;
	}
}
