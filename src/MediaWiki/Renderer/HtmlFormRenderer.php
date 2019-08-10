<?php

namespace SMW\MediaWiki\Renderer;

use Html;
use SMW\MediaWiki\MessageBuilder;
use Title;
use Xml;

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
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class HtmlFormRenderer {

	/**
	 * @var Title
	 */
	private $title = null;

	/**
	 * @var MessageBuilder
	 */
	private $messageBuilder = null;

	/**
	 * @var array
	 */
	private $queryParameters = [];

	/**
	 * @var string
	 */
	private $name ='';

	/**
	 * @var string|boolean
	 */
	private $method = false;

	/**
	 * @var string|boolean
	 */
	private $useFieldset = false;

	/**
	 * @var string|boolean
	 */
	private $actionUrl = false;

	/**
	 * @var string[]
	 */
	private $content = [];

	/**
	 * @var string
	 */
	private $defaultPrefix = 'smw-form';

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 * @param MessageBuilder $messageBuilder
	 */
	public function __construct( Title $title, MessageBuilder $messageBuilder ) {
		$this->title = $title;
		$this->messageBuilder = $messageBuilder;
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlFormRenderer
	 */
	public function clear() {
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
	 *
	 * @return MessageBuilder
	 */
	public function getMessageBuilder() {
		return $this->messageBuilder;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $name
	 *
	 * @return HtmlFormRenderer
	 */
	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $actionUrl
	 *
	 * @return HtmlFormRenderer
	 */
	public function setActionUrl( $actionUrl ) {
		$this->actionUrl = $actionUrl;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlFormRenderer
	 */
	public function withFieldset() {
		$this->useFieldset = true;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $method
	 *
	 * @return HtmlFormRenderer
	 */
	public function setMethod( $method ) {
		$this->method = strtolower( $method );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return HtmlFormRenderer
	 */
	public function addQueryParameter( $key, $value ) {
		$this->queryParameters[$key] = $value;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getQueryParameter() {
		return $this->queryParameters;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $description
	 * @param array $attributes
	 *
	 * @return HtmlFormRenderer
	 */
	public function addParagraph( $text, $attributes = [] ) {

		if ( $attributes === [] ) {
			$attributes = [ 'class' => $this->defaultPrefix . '-paragraph' ];
		}

		$this->content[] = Xml::tags( 'p', $attributes, $text );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param array $attributes
	 *
	 * @return HtmlFormRenderer
	 */
	public function addHorizontalRule( $attributes = [] ) {

		if ( $attributes === [] ) {
			$attributes = [ 'class' => $this->defaultPrefix . '-horizontalrule' ];
		}

		$this->content[] = Xml::tags( 'hr', $attributes, '' );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param $level
	 * @param $text
	 *
	 * @return HtmlFormRenderer
	 */
	public function addHeader( $level, $text, $attributes = [] ) {

		$level = strtolower( $level );
		$level = in_array( $level, [ 'h2', 'h3', 'h4' ] ) ? $level : 'h2';

		$this->content[] = Html::element( $level, $attributes, $text );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlFormRenderer
	 */
	public function addLineBreak() {
		$this->content[] = Html::element( 'br', [], '' );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlFormRenderer
	 */
	public function addNonBreakingSpace() {
		$this->content[] = '&nbsp;';
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string|null $text
	 *
	 * @return HtmlFormRenderer
	 */
	public function addSubmitButton( $text, $attributes = [] ) {
		$this->content[] = Xml::submitButton( $text, $attributes );
		return $this;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $element
	 * @param array $attributes
	 *
	 * @return HtmlFormRenderer
	 */
	public function openElement( $element = 'div', array $attributes = [] ) {
		$this->content[] = Html::openElement( $element, $attributes );
		return $this;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $element
	 * @param array $attributes
	 *
	 * @return HtmlFormRenderer
	 */
	public function closeElement( $element = 'div', array $attributes = [] ) {
		$this->content[] = Html::closeElement( $element, $attributes );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $label
	 * @param string $name
	 * @param string $value
	 * @param string|null $id
	 * @param integer $length
	 * @param array $attributes
	 *
	 * @return HtmlFormRenderer
	 */
	public function addInputField( $label, $name, $value, $id = null, $size = 20, array $attributes = [] ) {

		if ( $id === null ) {
			$id = $name;
		}

		$this->addQueryParameter( $name, $value );

		if ( !isset( $attributes['class'] ) ) {
			$attributes['class'] = $this->defaultPrefix . '-input';
		}

		$label = Xml::label( $label, $id, [] );
		$input = Xml::input( $name, $size, $value, [ 'id' => $id ] + $attributes );

		$this->content[] = $label . '&#160;' . $input;

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $inputName
	 * @param string $inputValue
	 *
	 * @return HtmlFormRenderer
	 */
	public function addHiddenField( $inputName, $inputValue ) {

		$this->addQueryParameter( $inputName, $inputValue );

		$this->content[] = Html::hidden( $inputName, $inputValue );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $label
	 * @param string $inputName
	 * @param string $inputValue
	 * @param array $options
	 * @param string|null $id
	 *
	 * @return HtmlFormRenderer
	 */
	public function addOptionSelectList( $label, $inputName, $inputValue, $options, $id = null ) {

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
				//	'disabled' => false,
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
	 *
	 * @param string $label
	 * @param string $inputName
	 * @param string $inputValue
	 * @param boolean $isChecked
	 * @param string|null $id
	 *
	 * @return HtmlFormRenderer
	 */
	public function addCheckbox( $label, $inputName, $inputValue, $isChecked = false, $id = null, $attributes = [] ) {

		if ( $id === null ) {
			$id = $inputName;
		}

		$this->addQueryParameter( $inputName, $inputValue );

		$html = Xml::checkLabel(
			$label,
			$inputName,
			$id,
			$isChecked,
			[
				'id' => $id,
				'class' => $this->defaultPrefix . '-checkbox',
				'value' => $inputValue
			] + ( $isChecked ? [ 'checked' => 'checked' ] : [] )
		);

		$this->content[] = Html::rawElement( 'span', $attributes, $html );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @note Encapsulate as closure to ensure that the build contains all query
	 * parameters that are necessary to build the paging links
	 *
	 * @param integer $limit
	 * @param integer $offset
	 * @param integer $count
	 * @param integer|null $messageCount
	 *
	 * @return HtmlFormRenderer
	 */
	public function addPaging( $limit, $offset, $count, $messageCount = null ) {

		$title = $this->title;

		$this->content[] = function( $instance ) use ( $title, $limit, $offset, $count, $messageCount ) {

			if ( $messageCount === null ) {
				$messageCount = ( $count > $limit ? $count - 1 : $count );
			}

			$resultCount = $instance->getMessageBuilder()
				->getMessage( 'showingresults' )
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
	 *
	 * @return string
	 */
	public function getForm() {

		$content = '';

		foreach ( $this->content as $value ) {
			$content .= is_callable( $value ) ? $value( $this ) : $value;
		}

		if ( $this->useFieldset ) {
			$content = Xml::fieldset(
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
			'action' => htmlspecialchars( $this->actionUrl ? $this->actionUrl : $GLOBALS['wgScript'] )
		], Html::hidden(
			'title',
			strtok( $this->title->getPrefixedText(), '/' )
		) . $content );

		$this->clear();

		return $form;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function renderForm() {
		return $this->getForm();
	}

}
