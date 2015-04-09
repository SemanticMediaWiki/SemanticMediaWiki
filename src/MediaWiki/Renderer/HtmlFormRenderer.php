<?php

namespace SMW\MediaWiki\Renderer;

use SMW\MediaWiki\MessageBuilder;
use Html;
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
	private $queryParameters = array();

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
	 * @var string
	 */
	private $content = array();

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
		$this->queryParameters = array();
		$this->content = array();
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
	public function addParagraph( $text, $attributes = array() ) {

		if ( $attributes === array() ) {
			$attributes = array( 'class' => $this->defaultPrefix . '-paragraph' );
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
	public function addHorizontalRule( $attributes = array() ) {

		if ( $attributes === array() ) {
			$attributes = array( 'class' => $this->defaultPrefix . '-horizontalrule' );
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
	public function addHeader( $level, $text ) {

		$level = strtolower( $level );
		$level = in_array( $level, array( 'h2', 'h3', 'h4' ) ) ? $level : 'h2';

		$this->content[] = Html::element( $level, array(), $text );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlFormRenderer
	 */
	public function addLineBreak() {
		$this->content[] = Html::element( 'br', array(), '' );
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
	public function addSubmitButton( $text ) {
		$this->content[] = Xml::submitButton( $text );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $label
	 * @param string $inputName
	 * @param string $inputValue
	 * @param string|null $id
	 * @param integer $length
	 * @param string $placeholder
	 *
	 * @return HtmlFormRenderer
	 */
	public function addInputField( $label, $inputName, $inputValue, $id = null, $length = 20, $placeholder = '' ) {

		if ( $id === null ) {
			$id = $inputName;
		}

		$this->addQueryParameter( $inputName, $inputValue );

		$this->content[] = Xml::inputLabel(
			$label,
			$inputName,
			$id,
			$length,
			$inputValue,
			array( 'class' => $this->defaultPrefix . '-input', 'placeholder' => $placeholder )
		);

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
		$optionsHtml = array();

		foreach ( $options as $internalId => $name ) {
			$optionsHtml[] = Html::element(
				'option', array(
				//	'disabled' => false,
					'value' => $internalId,
					'selected' => $internalId == $inputValue,
				), $name
			);
		}

		$html .= Html::element( 'label', array( 'for' => $id ), $label ) . '&#160;';

		$html .= Html::openElement(
			'select',
			array(
				'name' => $inputName,
				'id' => $id,
				'class' => $this->defaultPrefix . '-select' ) ) . "\n" .
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
	public function addCheckbox( $label, $inputName, $inputValue, $isChecked = false, $id = null ) {

		if ( $id === null ) {
			$id = $inputName;
		}

		$this->addQueryParameter( $inputName, $inputValue );

		$html = Xml::checkLabel(
			$label,
			$inputName,
			$id,
			$isChecked,
			array(
				'id' => $id,
				'class' => $this->defaultPrefix . '-checkbox',
				'value' => $inputValue ) + ( $isChecked ? array( 'checked' => 'checked' ) : array() )
		);

		$this->content[] = $html;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @note Encapsulate as closure to ensure that the build contains all query
	 * parameters that are necessary to build the paging links
	 *
	 * @param integer $limit,
	 * @param integer $offset,
	 * @param integer $count,
	 *
	 * @return HtmlFormRenderer
	 */
	public function addPaging( $limit, $offset, $count ) {

		$title = $this->title;

		$this->content[] = function( $instance ) use ( $title, $limit, $offset, $count ) {

			$resultCount = $instance->getMessageBuilder()
				->getMessage( 'showingresults' )
				->numParams( ( $count > $limit ? $count - 1 : $count ), $offset + 1 )
				->parse();

			$paging = $instance->getMessageBuilder()->prevNextToText(
				$title,
				$limit,
				$offset,
				$instance->getQueryParameter(),
				$count < $limit
			);

			return Xml::tags( 'p', array(), $resultCount ) . Xml::tags( 'p', array(), $paging );
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
				array(
					'id' => $this->defaultPrefix . "-fieldset-{$this->name}"
				)
			);
		}

		$form = Xml::tags( 'form', array(
			'id'     => $this->defaultPrefix . "-{$this->name}",
			'name'   => $this->name,
			'method' => in_array( $this->method, array( 'get', 'post' ) ) ? $this->method : 'get',
			'action' => htmlspecialchars( $this->actionUrl ? $this->actionUrl : $GLOBALS['wgScript'] )
		), Html::hidden(
			'title',
			strtok( $this->title->getPrefixedText(), '/' )
		) . $content );

		$this->clear();

		return $form;
	}

}
