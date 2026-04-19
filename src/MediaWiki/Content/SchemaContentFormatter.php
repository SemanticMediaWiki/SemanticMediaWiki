<?php

namespace SMW\MediaWiki\Content;

use MediaWiki\MediaWikiServices;
use Onoi\CodeHighlighter\Geshi;
use Onoi\CodeHighlighter\Highlighter as CodeHighlighter;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Formatters\Infolink;
use SMW\Localizer\Message;
use SMW\MediaWiki\Page\ListBuilder;
use SMW\Schema\Schema;
use SMW\Store;
use SMW\Utils\Html\SummaryTable;
use Traversable;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentFormatter {

	private HtmlBuilder $htmlBuilder;

	private bool $isYaml = false;

	private array $type = [];

	private string|null|false $unknownType = false;

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly Store $store ) {
		$this->htmlBuilder = new HtmlBuilder();
	}

	/**
	 * @since 3.0
	 */
	public function isYaml( bool $isYaml ): void {
		$this->isYaml = $isYaml;
	}

	/**
	 * @since 3.0
	 */
	public function setType( $type ): void {
		$this->type = $type;
	}

	/**
	 * @since 3.0
	 */
	public function getModuleStyles(): array {
		return array_merge( [
			'mediawiki.helplink',
			'smw.content.schema',
			'mediawiki.content.json',
			'ext.smw.styles',
			'ext.smw.table.styles',
		], SummaryTable::getModuleStyles() );
	}

	/**
	 * @since 3.0
	 */
	public function getModules(): array {
		return [ 'smw.content.schemaview' ];
	}

	/**
	 * @since 3.0
	 */
	public function getHelpLink( Schema $schema ): string {
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
	 */
	public function setUnknownType( string $type ): void {
		$this->unknownType = $type;
	}

	/**
	 * @since 3.0
	 */
	public function getText( string $text, ?Schema $schema = null, array $errors = [] ): string {
		$methods = [
			'body'   => [ $schema, $errors, $text ],
		// 'footer' => [ $schema ]
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
	 */
	public function getUsage( ?Schema $schema = null ): array {
		if ( $schema === null || !isset( $this->type['usage_lookup'] ) ) {
			return [ '', 0 ];
		}

		$usage = '';
		$dataItems = [];

		$usage_lookup = (array)$this->type['usage_lookup'];

		$subject = new WikiPage(
			str_replace( ' ', '_', $schema->getName() ?? '' ),
			SMW_NS_SCHEMA
		);

		foreach ( $usage_lookup as $property ) {
			$property = new Property(
				$property
			);

			$ps = $this->store->getPropertySubjects( $property, $subject );

			if ( $ps instanceof Traversable ) {
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

		[ $usage, $usage_count ] = $this->getUsage( $schema );

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

	private function schema_summary( $schema, array $errors ) {
		$errorCount = count( $errors );
		$type = $schema->get( Schema::SCHEMA_TYPE );

		$schema_link = pathinfo(
			$schema->info( Schema::SCHEMA_VALIDATION_FILE ) ?? '', PATHINFO_FILENAME
		);

		if ( isset( $this->type['type_description'] ) ) {
			$type_description = $this->msg( [ $this->type['type_description'], $type ], Message::PARSE );
		} else {
			$type_description = '';
		}

		$attributes = [
			'schema_description' => $schema->get( Schema::SCHEMA_DESCRIPTION, '' ),
			'type' => $type,
			'type_description' => $type_description,
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
			'error_params' => $this->error_params( $errors ),
			'error-title' => $this->msg( [ 'smw-schema-error-title', $errorCount ] ),
		];

		return $this->htmlBuilder->build( 'schema_summary', $params );
	}

	private function schema_body( $text ) {
		$codeHighlighter = null;

		// @phan-suppress-next-line PhanUndeclaredClassReference
		if ( class_exists( CodeHighlighter::class ) ) {
			$codeHighlighter = new CodeHighlighter();

			// `yaml` works well enough for both JSON and YAML
			$codeHighlighter->setLanguage( 'yaml' );
			// @phan-suppress-next-line PhanUndeclaredClassConstant
			$codeHighlighter->addOption( Geshi::SET_OVERALL_CLASS, 'content-highlight' );
		}

		if ( $codeHighlighter !== null && $this->isYaml ) {
			$text = $codeHighlighter->highlight( $text );
		} elseif ( $codeHighlighter !== null ) {
			// @phan-suppress-next-line PhanUndeclaredClassConstant
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

	private function attributes_extra( $schema ): array {
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

		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$params = [
			'href_type' => $titleFactory->newFromText( 'Schema type', SMW_NS_PROPERTY )->getLocalUrl(),
			'msg_type'  => $this->msg( [ 'smw-schema-type' ] ),
			'link_type' => $link->getHtml(),
			'href_tag'  => $titleFactory->newFromText( 'Schema tag', SMW_NS_PROPERTY )->getLocalUrl(),
			'msg_tag'   => $this->msg( [ 'smw-schema-tag', count( $tags ) ] ),
			'tags'      => $tags,
			'href_description' => $titleFactory->newFromText( 'Schema description', SMW_NS_PROPERTY )->getLocalUrl(),
			'msg_description'  => $this->msg( [ 'smw-schema-description' ] ),
		];

		return $params;
	}

	private function error_params( array $errors = [] ): array {
		if ( $errors === [] ) {
			return [];
		}

		$list = [];

		foreach ( $errors as $error ) {

			if ( isset( $error['property'] ) ) {
				$list[$error['property']] = $error['message'];
			} elseif ( is_array( $error ) ) {
				$list[$this->msg( [ 'smw-schema-error-miscellaneous', $error[0] ?? 'n/a' ] )] = $this->msg( $error );
			}
		}

		if ( $list === [] ) {
			return [];
		}

		return $list;
	}

	private function unknown_type( $type ) {
		if ( $type === '' || $type === null ) {
			$key = 'smw-schema-error-type-missing';
		} else {
			$key = [ 'smw-schema-error-type-unknown', $type ];
		}

		$params = [
			'msg' => $this->msg( $key, Message::PARSE )
		];

		return $this->htmlBuilder->build( 'schema_unknown_type', $params );
	}

	private function msg( array|string $key, int $type = Message::TEXT, $lang = Message::USER_LANGUAGE ): string {
		return Message::get( $key, $type, $lang );
	}

}
