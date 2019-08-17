<?php

namespace SMW\MediaWiki\Content;

use SMW\Schema\Schema;
use SMW\Schema\SchemaFactory;
use SMW\Message;
use SMW\Store;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWInfolink as Infolink;
use Onoi\CodeHighlighter\Highlighter as CodeHighlighter;
use Onoi\CodeHighlighter\Geshi;
use SMW\MediaWiki\Page\ListBuilder;
use SMW\Utils\Html\SummaryTable;
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
	 * @var Store
	 */
	private $store;

	/**
	 * @var HtmlBuilder
	 */
	private $htmlBuilder;

	/**
	 * @var boolean
	 */
	private $isYaml = false;

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
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
		$this->htmlBuilder = new HtmlBuilder();
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isYaml
	 */
	public function isYaml( $isYaml ) {
		$this->isYaml = $isYaml;
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
		return array_merge( [
			'mediawiki.helplink',
			'smw.content.schema',
			'mediawiki.content.json',
			'ext.smw.style',
			'ext.smw.table.styles',
			'smw.factbox',
		], SummaryTable::getModuleStyles() );
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
	public function getText( $text, Schema $schema = null, array $errors = [] ) {

		$methods = [
			'body'   => [ $schema, $errors, $text ],
		//	'footer' => [ $schema ]
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

	/**
	 * @since 3.1
	 *
	 * @param Schema|null $schema
	 *
	 * @return array
	 */
	public function getUsage( Schema $schema = null ) {

		if ( $schema === null || !isset( $this->type['usage_lookup'] ) ) {
			return [ '', 0 ];
		}

		$usage = '';
		$dataItems = [];

		$usage_lookup = (array)$this->type['usage_lookup'];

		$subject = new DIWikiPage(
			str_replace(' ', '_', $schema->getName() ),
			SMW_NS_SCHEMA
		);

		foreach ( $usage_lookup as $property ) {
			$property = new DIProperty(
				$property
			);

			$ps = $this->store->getPropertySubjects( $property, $subject );

			if ( $ps instanceof \Traversable ) {
				$ps = iterator_to_array( $ps );
			}

			$dataItems = array_merge( $dataItems, $ps );
		}

		if ( $dataItems !== [] ) {
			$usageCount = count( $dataItems );
			$listBuilder = new ListBuilder( $this->store );
			$usage = $listBuilder->getColumnList( $dataItems );
		} else {
			$usageCount = 0;
		}

		return [ $usage, $usageCount ];
	}

	private function body( $schema, array $errors, $text ) {

		if ( $schema === null ) {
			return '';
		}

		list( $usage, $usage_count ) = $this->getUsage( $schema );

		$params = [
			'link' => '',
			'description' => '',
			'type_description' => '',
			'usage' => $usage,
			'usage_count' => $usage_count,
			'schema_summary' => $this->schema_summary( $schema, $errors ),
			'schema_body' => $this->schema_body( $text ),
			'summary-title' => $this->msg( 'smw-schema-summary-title' ),
			'schema-title' => $this->msg( 'smw-schema-title' ),
			'usage-title' => $this->msg( 'smw-schema-usage' )
		];

		return $this->htmlBuilder->build( 'schema_head', $params );
	}

	private function schema_summary( $schema, $errors ) {

		$errorCount = count( $errors );
		$type = $schema->get( Schema::SCHEMA_TYPE );

		$schema_link = pathinfo(
			$schema->info( Schema::SCHEMA_VALIDATION_FILE ), PATHINFO_FILENAME
		);

		if ( isset( $this->type['type_description'] ) ) {
			$type_description = $this->msg( [ $this->type['type_description'], $type ], Message::PARSE );
		} else {
			$type_description = '';
		}

		$attributes = [
			'type_description' => $type_description,
			'schema_description' => $schema->get( Schema::SCHEMA_DESCRIPTION, '' ),
			'type' => $type,
			'tag' => $schema->get( Schema::SCHEMA_TAG )
		];

		$params = [
			'attributes' => $attributes,
			'attributes_extra' => $this->attributes_extra( $schema ) +
				[
					'type_description' => $this->msg( 'smw-schema-type-description' )
				],
			'validator-schema-title' => $this->msg( [ 'smw-schema-validation-schema-title' ] ),
			'validator_schema' => $schema_link,
			'error_params' => $this->error_params( $schema_link, $errors ),
			'error-title' => $this->msg( [ 'smw-schema-error-title', $errorCount ] ),
		];

		return $this->htmlBuilder->build( 'schema_summary', $params );
	}

	private function schema_body( $text ) {

		$codeHighlighter = null;

		if ( class_exists( '\Onoi\CodeHighlighter\Highlighter' ) ) {
			$codeHighlighter = new CodeHighlighter();

			// `yaml` works well enough for both JSON and YAML
			$codeHighlighter->setLanguage( 'yaml' );
			$codeHighlighter->addOption( Geshi::SET_OVERALL_CLASS, 'content-highlight' );
		}

		if ( $codeHighlighter !== null && $this->isYaml ) {
			$text = $codeHighlighter->highlight( $text );
		} elseif ( $codeHighlighter !== null ) {
			$codeHighlighter->addOption( Geshi::SET_STRINGS_STYLE, 'color: #000' );
			$text = $codeHighlighter->highlight( $text );
		} else {
			if ( !$this->isYaml ) {
				$text = json_encode( json_decode( $text ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		}

		$params = [
			'text' => $text,
			'isYaml' => $this->isYaml,
			'unknown_type' => $this->unknownType
		];

		return $this->htmlBuilder->build( 'schema_body', $params );
	}

	private function attributes_extra( $schema ) {

		if ( $schema === null ) {
			return [];
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
			'tags'      => $tags,
			'href_description' => Title::newFromText( 'Schema description', SMW_NS_PROPERTY )->getLocalUrl(),
			'msg_description'  => $this->msg( [ 'smw-schema-description' ] ),
		];

		return $params;
	}

	private function error_params( $validator_schema, array $errors = [] ) {

		if ( $errors === [] ) {
			return [];
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

			$list[$error['property']] = $error['message'];
		}

		if ( $list === [] ) {
			return [];
		}

		return $list;
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
