<?php

namespace SMW\MediaWiki\Content;

use SMW\Schema\Schema;
use SMW\Schema\SchemaFactory;
use SMW\Message;
use SMWInfolink as Infolink;
use Onoi\CodeHighlighter\Highlighter as CodeHighlighter;
use Onoi\CodeHighlighter\Geshi;
use Html;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentFormatter {

	/**
	 * @var HtmlBuilder
	 */
	private $htmlBuilder;

	/**
	 * @var []
	 */
	private $type = [];

	/**
	 * @var string|null
	 */
	private $unknownType = false;

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function __construct() {
		$this->htmlBuilder = new HtmlBuilder();
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function setType( $type ) {
		$this->type = $type;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getModuleStyles() {
		return [ 'mediawiki.helplink', 'smw.content.schema', 'mediawiki.content.json' ];
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getModules() {
		return [ 'smw.content.schemaview' ];
	}

	/**
	 * @since 3.0
	 *
	 * @param Schema $schema
	 *
	 * @return string
	 */
	public function getHelpLink( Schema $schema ) {

		$key = [
			'smw-schema-type-help-link',
			$schema->get( Schema::SCHEMA_TYPE )
		];

		$params = [
			'href' => $this->msg( $key )
		];

		return $this->htmlBuilder->build( 'schema_help_link', $params );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 */
	public function setUnknownType( $type ) {
		$this->unknownType = $type;
	}

	/**
	 * @since 3.0
	 *
	 * @param Schema $schema
	 *
	 * @return string
	 */
	public function getText( $text, $isYaml = false, Schema $schema = null,  array $errors = [] ) {

		$methods = [
			'head'   => [ $schema, $errors ],
			'body'   => [ $text, $isYaml ],
			'footer' => [ $schema ]
		];

		$html = '';

		if ( $this->unknownType !== false ) {
			$html = $this->unknown_type( $this->unknownType );
		}

		foreach ( $methods as $method => $element ) {
			$html .= $this->{$method}( ...$element );
		}

		return $html;
	}

	private function head( $schema, array $errors ) {

		if ( $schema === null ) {
			return '';
		}

		$schema_link = str_replace( '.json', '', substr(
			$schema->getValidationSchema(),
			strrpos( $schema->getValidationSchema(), '/' ) + 1
		) );

		$errorCount = count( $errors );
		$error = $this->error_text( $schema_link, $errors );

		$type = $schema->get( 'type', '' );
		$description = '';

		if ( isset( $this->type['type_description'] ) ) {
			$description = $this->msg( $this->type['type_description'], Message::PARSE );
		}

		$params = [
			'link' => '',
			'description' => $schema->get( Schema::SCHEMA_DESCRIPTION, '' ),
			'type_description' => $description,
			'schema-title' => $this->msg( 'smw-schema-title' ),
			'error' => $error,
			'error-title' => $this->msg( [ 'smw-schema-error', $errorCount ] )
		];

		return $this->htmlBuilder->build( 'schema_head', $params );
	}

	private function body( $text, $isYaml ) {

		$codeHighlighter = null;

		if ( class_exists( '\Onoi\CodeHighlighter\Highlighter' ) ) {
			$codeHighlighter = new CodeHighlighter();

			// `yaml` works well enough for both JSON and YAML
			$codeHighlighter->setLanguage( 'yaml' );
			$codeHighlighter->addOption( Geshi::SET_OVERALL_CLASS, 'content-highlight' );
		}

		if ( $codeHighlighter !== null && $isYaml ) {
			$text = $codeHighlighter->highlight( $text );
		} elseif ( $codeHighlighter !== null ) {
			$codeHighlighter->addOption( Geshi::SET_STRINGS_STYLE, 'color: #000' );
			$text = $codeHighlighter->highlight( $text );
		} else {
			if ( !$isYaml ) {
				$text = json_encode( json_decode( $text ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		}

		$params = [
			'text' => $text,
			'isYaml' => $isYaml,
			'unknown_type' => $this->unknownType
		];

		return $this->htmlBuilder->build( 'schema_body', $params );
	}

	private function footer( $schema ) {

		if ( $schema === null ) {
			return '';
		}

		$tags = [];

		if ( ( $tags = $schema->get( Schema::SCHEMA_TAG, [] ) ) !== [] ) {
			foreach ( $tags as $k => $tag ) {
				$tags[$k] = Infolink::newPropertySearchLink( $tag, 'Schema tag', $tag, '' )->getHtml();
			}
		}

		$type = $schema->get( 'type', '' );
		$link = Infolink::newPropertySearchLink( $type, 'Schema type', $type, '' );

		$params = [
			'href_type' => Title::newFromText( 'Schema type', SMW_NS_PROPERTY )->getLocalUrl(),
			'msg_type'  => $this->msg( [ 'smw-schema-type' ] ),
			'link_type' => $link->getHtml(),
			'href_tag'  => Title::newFromText( 'Schema tag', SMW_NS_PROPERTY )->getLocalUrl(),
			'msg_tag'   => $this->msg( [ 'smw-schema-tag', count( $tags ) ] ),
			'tags'      => $tags
		];

		return $this->htmlBuilder->build( 'schema_footer', $params );
	}

	private function error_text( $validator_schema, array $errors = [] ) {

		if ( $errors === [] ) {
			return '';
		}

		$list = [];

		foreach ( $errors as $error ) {

			if ( !isset( $error['property'] ) ) {
				continue;
			}

			$params = [
				'msg' => $error['message'],
				'text' => $error['property']
			];

			$list[] = $this->htmlBuilder->build( 'schema_error', $params );
		}

		if ( $list === [] ) {
			return '';
		}

		$params = [
			'list' => $list,
			'schema' => $this->msg( [ 'smw-schema-error-schema', $validator_schema ], Message::PARSE )
		];

		return $this->htmlBuilder->build( 'schema_error_text', $params );
	}

	private function unknown_type( $type ) {

		if (  $type === '' || $type === null ) {
			$key = 'smw-schema-error-type-missing';
		} else {
			$key = [ 'smw-schema-error-type-unknown', $type ];
		}

		$params = [
			'msg' => $this->msg( $key, Message::PARSE )
		];

		return $this->htmlBuilder->build( 'schema_unknown_type', $params );
	}

	private function msg( $key, $type = Message::TEXT, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $key, $type, $lang );
	}

}
