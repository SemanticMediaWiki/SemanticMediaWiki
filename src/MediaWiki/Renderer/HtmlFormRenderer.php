<?php

namespace SMW\MediaWiki\Renderer;

use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use MediaWiki\Xml\Xml;
use SMW\MediaWiki\MessageBuilder;

/**
 * Convenience class to build a html form by using a fluid interface
 *
 * @par Example:
 * @code
 * $htmlFormRenderer = new HtmlFormRenderer( $this->title, new MessageBuilder() );
 * $htmlFormRenderer
 * 	->setName( 'Foo' )
 * 	->setParameter( 'foo', 'someValue' )
 * 	->addPaging( 10, 0, 5 )
 * 	->addHorizontalRule()
 * 	->addInputField( 'BarLabel', 'bar', 'someValue' )
 * 	->addSubmitButton()
 * 	->getForm();
 * @endcode
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class HtmlFormRenderer {

	private array $queryParameters = [];

	private string $name = '';

	private string|bool $method = false;

	private bool $useFieldset = false;

	private string|bool $actionUrl = false;

	private array $content = [];

	private string $defaultPrefix = 'smw-form';

	/**
	 * @since 2.1
	 */
	public function __construct(
		private readonly Title $title,
		private readonly MessageBuilder $messageBuilder,
	) {
	}

	/**
	 * @since 2.1
	 */
	public function clear(): static {
		$this->queryParameters = [];
		$this->content = [];
		$this->name = '';
		$this->method = false;
		$this->useFieldset = false;
		$this->actionUrl = false;

		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function getMessageBuilder(): MessageBuilder {
		return $this->messageBuilder;
	}

	/**
	 * @since 2.1
	 */
	public function setName( string $name ): static {
		$this->name = $name;
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function setActionUrl( string $actionUrl ): static {
		$this->actionUrl = $actionUrl;
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function withFieldset(): static {
		$this->useFieldset = true;
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function setMethod( string $method ): static {
		$this->method = strtolower( $method );
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addQueryParameter( string $key, mixed $value ): static {
		$this->queryParameters[$key] = $value;
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function getQueryParameter(): array {
		return $this->queryParameters;
	}

	/**
	 * @since 2.1
	 */
	public function addParagraph( string $text, array $attributes = [] ): static {
		if ( $attributes === [] ) {
			$attributes = [ 'class' => $this->defaultPrefix . '-paragraph' ];
		}

		$this->content[] = Xml::tags( 'p', $attributes, $text );
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addHorizontalRule( array $attributes = [] ): static {
		if ( $attributes === [] ) {
			$attributes = [ 'class' => $this->defaultPrefix . '-horizontalrule' ];
		}

		$this->content[] = Xml::tags( 'hr', $attributes, '' );
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addHeader( string $level, string $text, array $attributes = [] ): static {
		$level = strtolower( $level );
		$level = in_array( $level, [ 'h2', 'h3', 'h4' ] ) ? $level : 'h2';

		$this->content[] = Html::element( $level, $attributes, $text );
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addLineBreak(): static {
		$this->content[] = Html::element( 'br', [], '' );
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addNonBreakingSpace(): static {
		$this->content[] = '&nbsp;';
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addSubmitButton( ?string $text = null, array $attributes = [] ): static {
		$this->content[] = Html::submitButton( $text, $attributes );
		return $this;
	}

	/**
	 * @since 3.0
	 */
	public function openElement( string $element = 'div', array $attributes = [] ): static {
		$this->content[] = Html::openElement( $element, $attributes );
		return $this;
	}

	/**
	 * @since 3.0
	 */
	public function closeElement( string $element = 'div' ): static {
		$this->content[] = Html::closeElement( $element );
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addInputField(
		?string $label,
		string $name,
		mixed $value,
		?string $id = null,
		int $size = 20,
		array $attributes = []
	): static {
		if ( $id === null ) {
			$id = $name;
		}

		$this->addQueryParameter( $name, $value );

		if ( !isset( $attributes['class'] ) ) {
			$attributes['class'] = $this->defaultPrefix . '-input';
		}

		$label = Html::label( $label, $id, [] );
		$input = Html::input( $name, $value, 'text', [ 'size' => $size, 'id' => $id ] + $attributes );

		$this->content[] = $label . '&#160;' . $input;

		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addHiddenField( string $inputName, string $inputValue ): static {
		$this->addQueryParameter( $inputName, $inputValue );

		$this->content[] = Html::hidden( $inputName, $inputValue );
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addOptionSelectList(
		string $label,
		string $inputName,
		string $inputValue,
		array $options,
		?string $id = null
	): static {
		if ( $id === null ) {
			$id = $inputName;
		}

		$this->addQueryParameter( $inputName, $inputValue );

		ksort( $options );

		$html = '';
		$optionsHtml = [];

		foreach ( $options as $internalId => $name ) {
			$optionsHtml[] = Html::element(
				'option', [
				// 'disabled' => false,
					'value' => $internalId,
					'selected' => $internalId == $inputValue,
				], $name
			);
		}

		$html .= Html::element( 'label', [ 'for' => $id ], $label ) . '&#160;';

		$html .= Html::openElement(
			'select',
			[
				'name' => $inputName,
				'id' => $id,
				'class' => $this->defaultPrefix . '-select' ] ) . "\n" .
			implode( "\n", $optionsHtml ) . "\n" .
			Html::closeElement( 'select' );

		$this->content[] = $html;
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function addCheckbox(
		string $label,
		string $inputName,
		string $inputValue,
		bool $isChecked = false,
		?string $id = null,
		array $attributes = []
	): static {
		if ( $id === null ) {
			$id = $inputName;
		}

		$this->addQueryParameter( $inputName, $inputValue );

		$html = HtmlUtil::checkLabel(
			$label,
			$inputName,
			$id,
			$isChecked,
			[
				'id' => $id,
				'class' => $this->defaultPrefix . '-checkbox',
				'value' => $inputValue
			]
		);

		$this->content[] = Html::rawElement( 'span', $attributes, $html );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @note Encapsulate as closure to ensure that the build contains all query
	 * parameters that are necessary to build the paging links
	 */
	public function addPaging(
		int $limit,
		int $offset,
		int $count,
		?int $messageCount = null
	): static {
		$title = $this->title;

		$this->content[] = static function ( $instance ) use ( $title, $limit, $offset, $count, $messageCount ): string {
			if ( $messageCount === null ) {
				$messageCount = ( $count > $limit ? $count - 1 : $count );
			}

			$resultCount = $instance->getMessageBuilder()
				->getMessage( 'smw-showingresults' )
				->numParams( $messageCount, $offset + 1 )
				->parse();

			$paging = $instance->getMessageBuilder()->prevNextToText(
				$title,
				$limit,
				$offset,
				$instance->getQueryParameter(),
				$count < $limit
			);

			return Xml::tags( 'p', [], $resultCount ) . Xml::tags( 'p', [], $paging );
		};

		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function getForm(): string {
		$content = '';

		foreach ( $this->content as $value ) {
			$content .= is_callable( $value ) ? $value( $this ) : $value;
		}

		if ( $this->useFieldset ) {
			$content = HtmlUtil::fieldset(
				$this->messageBuilder->getMessage( $this->name )->text(),
				$content,
				[
					'id' => $this->defaultPrefix . "-fieldset-{$this->name}"
				]
			);
		}

		$form = Xml::tags( 'form', [
			'id'     => $this->defaultPrefix . "-{$this->name}",
			'name'   => $this->name,
			'method' => in_array( $this->method, [ 'get', 'post' ] ) ? $this->method : 'get',
			'action' => htmlspecialchars( $this->actionUrl ?: $GLOBALS['wgScript'] )
		], Html::hidden(
			'title',
			strtok( $this->title->getPrefixedText() ?? '', '/' )
		) . $content );

		$this->clear();

		return $form;
	}

	/**
	 * @since 3.0
	 */
	public function renderForm(): string {
		return $this->getForm();
	}

}
