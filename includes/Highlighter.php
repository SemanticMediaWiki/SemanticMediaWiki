<?php

namespace SMW;

use Html;
use SMWOutputs;

/**
 * Highlighter utility function for Semantic MediaWiki
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class Highlighter {

	/**
	 * Highlighter ID for no types
	 */
	const TYPE_NOTYPE = 0;

	/**
	 * Highlighter ID for properties
	 */
	const TYPE_PROPERTY = 1;

	/**
	 * Highlighter ID for text
	 */
	const TYPE_TEXT = 2;

	/**
	 * Highlighter ID for quantities
	 */
	const TYPE_QUANTITY = 3;

	/**
	 * Highlighter ID for warnings
	 */
	const TYPE_WARNING = 4;

	/**
	 * Highlighter ID for error
	 */
	const TYPE_ERROR = 5;

	/**
	 * Highlighter ID for information
	 */
	const TYPE_INFO = 6;

	/**
	 * Highlighter ID for help
	 */
	const TYPE_HELP = 7;

	/**
	 * Highlighter ID for notes
	 */
	const TYPE_NOTE = 8;

	/**
	 * Highlighter ID for service links
	 */
	const TYPE_SERVICE = 9;

	/**
	 * Highlighter ID for reference links
	 */
	const TYPE_REFERENCE = 10;

	/**
	 * @var array $options
	 */
	private $options;

	/**
	 * @var int $type
	 */
	private $type;

	/**
	 * @var string|null
	 */
	private $language = null;

	/**
	 * @since 1.9
	 *
	 * @param int $type
	 * @param string|null $language
	 */
	public function __construct( $type, $language = null ) {
		$this->type = $type;
		$this->language = $language;
	}

	/**
	 * @since 1.9
	 *
	 * @param string|int $type
	 * @param string|null $language
	 *
	 * @return Highlighter
	 */
	public static function factory( $type, $language = null ) {
		if ( $type === '' || !is_int( $type ) ) {
			$type = self::getTypeId( $type );
		}

		return new Highlighter( $type, $language );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 * @param string|null $type
	 *
	 * @return booelan
	 */
	public static function hasHighlighterClass( $text, $type = null ) {

		if ( strpos( $text, 'smw-highlighter' ) === false ) {
			return false;
		}

		if ( $type !== null ) {
			return strpos( $text, 'data-type="' . self::getTypeId( $type ) . '"' ) !== false;
		}

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function decode( $text ) {
		// #2347, '[' is handled by the MediaWiki parser/sanitizer itself
		return str_replace(
			[ '&amp;', '&lt;', '&gt;', '&#160;', '<nowiki>', '</nowiki>' ],
			[ '&', '<', '>', ' ', '', '' ],
			$text
		);
	}

	/**
	 * Returns html output
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getHtml() {
		SMWOutputs::requireResource( 'ext.smw.tooltips' );
		return $this->getContainer();
	}

	/**
	 * Set content
	 *
	 * An external class that invokes content via setContent has to ensure
	 * that all data are appropriately escaped.
	 *
	 * @since 1.9
	 *
	 * @param array $content
	 *
	 * @return array
	 */
	public function setContent( array $content ) {
		/**
		 * @var $content
		 * $content['caption'] = a text or null
		 * $content['context'] = a text or null
		 */
		return $this->options = array_merge( $this->getTypeConfiguration( $this->type ), $content );
	}

	/**
	 * Returns type id
	 *
	 * @since 1.9
	 *
	 * @param string $type
	 *
	 * @return integer
	 */
	public static function getTypeId( $type ) {
		// TODO: why do we have a htmlspecialchars here?!
		switch ( strtolower ( htmlspecialchars ( $type ) ) ) {
			case 'property':
			return self::TYPE_PROPERTY;
			case 'text':
			return self::TYPE_TEXT;
			case 'quantity':
			return self::TYPE_QUANTITY;
			case 'warning':
			return self::TYPE_WARNING;
			case 'error':
			return self::TYPE_ERROR;
			case 'info':
			return self::TYPE_INFO;
			case 'help':
			return self::TYPE_HELP;
			case 'note':
			return self::TYPE_NOTE;
			case 'service':
			return self::TYPE_SERVICE;
			case 'reference':
			return self::TYPE_REFERENCE;
			default:
			return self::TYPE_NOTYPE;
		}
	}

	/**
	 * Builds Html container
	 *
	 * Content that is being invoked has to be escaped
	 * @see Highlighter::setContent
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	private function getContainer() {

		$captionclass = $this->options['captionclass'];

		// 2.4+ can display context for user-defined properties, here we ensure
		// to keep the style otherwise it displays italic which is by convention
		// reserved for predefined properties
		if ( $this->type === self::TYPE_PROPERTY && isset( $this->options['userDefined'] ) ) {
			$captionclass = $this->options['userDefined'] ? 'smwtext' : $captionclass;
		}

		$language = is_string( $this->language ) ? $this->language : Message::USER_LANGUAGE;
		$style = [];

		if ( isset( $this->options['style'] ) ) {
			$style = [ 'style' => $this->options['style'] ];
		}

		// In case the text contains HTML, remove trailing line feeds to avoid breaking
		// the display
		if ( $this->options['content'] != strip_tags( $this->options['content'] ) ) {
			$this->options['content'] = str_replace( [ "\n" ], [ '' ], $this->options['content'] );
		}

		// #1875
		// title attribute contains stripped content to allow for a display in
		// no-js environments, the tooltip will remove the element once it is
		// loaded
		$title = $this->title( $this->options['content'], $language );

		$html = Html::rawElement(
			'span',
			[
				'class'        => 'smw-highlighter',
				'data-type'    => $this->options['type'],
				'data-content' => isset( $this->options['data-content'] ) ? $this->options['data-content'] : null,
				'data-state'   => $this->options['state'],
				'data-title'   => Message::get( $this->options['title'], Message::TEXT, $language ),
				'title'        => $title
			] + $style,
			Html::rawElement(
				'span',
				[
					'class' => $captionclass
				],
				$this->options['caption']
			) . Html::rawElement(
				'span',
				[
					'class' => 'smwttcontent'
				],
				// Embedded wiki content that has other elements like (e.g. <ul>/<ol>)
				// will make the parser go berserk (injecting <p> elements etc.)
				// hence encode the identifying </> and decode it within the
				// tooltip
				str_replace( [ "\n", '<', '>' ], [ '</br>', '&lt;', '&gt;' ], htmlspecialchars_decode( $this->options['content'] ) )
			)
		);

		return $html;
	}

	/**
	 * Returns initial configuration settings
	 *
	 * @note You could create a class per entity type but does this
	 * really make sense just to get some configuration parameters?
	 *
	 * @since 1.9
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	private function getTypeConfiguration( $type ) {
		$settings = [];
		$settings['type'] = $type;
		$settings['caption'] = '';
		$settings['content'] = '';

		switch ( $type ) {
			case self::TYPE_PROPERTY:
				$settings['state'] = 'inline';
				$settings['title'] = 'smw-ui-tooltip-title-property';
				$settings['captionclass'] = 'smwbuiltin';
				break;
			case self::TYPE_TEXT:
				$settings['state'] = 'persistent';
				$settings['title'] = 'smw-ui-tooltip-title-info';
				$settings['captionclass'] = 'smwtext';
				break;
			case self::TYPE_QUANTITY:
				$settings['state'] = 'inline';
				$settings['title'] = 'smw-ui-tooltip-title-quantity';
				$settings['captionclass'] = 'smwtext';
				break;
			case self::TYPE_NOTE:
				$settings['state'] = 'inline';
				$settings['title'] = 'smw-ui-tooltip-title-note';
				$settings['captionclass'] = 'smwtticon note';
				break;
			case self::TYPE_WARNING:
				$settings['state'] = 'inline';
				$settings['title'] = 'smw-ui-tooltip-title-warning';
				$settings['captionclass'] = 'smwtticon warning';
				break;
			case self::TYPE_ERROR:
				$settings['state'] = 'inline';
				$settings['title'] = 'smw-ui-tooltip-title-error';
				$settings['captionclass'] = 'smwtticon error';
				break;
			case self::TYPE_SERVICE:
				$settings['state'] = 'persistent';
				$settings['title'] = 'smw-ui-tooltip-title-service';
				$settings['captionclass'] = 'smwtticon service';
				break;
			case self::TYPE_REFERENCE:
				$settings['state'] = 'persistent';
				$settings['title'] = 'smw-ui-tooltip-title-reference';
				$settings['captionclass'] = 'smwtext';
				break;
			case self::TYPE_HELP:
			case self::TYPE_INFO:
				$settings['state'] = 'persistent';
				$settings['title'] = 'smw-ui-tooltip-title-info';
				$settings['captionclass'] = 'smwtticon info';
				break;
			case self::TYPE_NOTYPE:
			default:
				$settings['state'] = 'persistent';
				$settings['title'] = 'smw-ui-tooltip-title-info';
				$settings['captionclass'] = 'smwbuiltin';
		};

		return $settings;
	}

	private function title( $content, $language ) {

		// Pre-process the content when used as title to avoid breaking elements
		// (URLs etc.)
		if ( strpos( $content, '[' ) !== false || strpos( $content, '//' ) !== false ) {
			$content = Message::get( [ 'smw-parse', $content ], Message::PARSE, $language );
		}

		return strip_tags( htmlspecialchars_decode( str_replace( [ "[", '&#160;', "&#10;", "\n" ], [ "&#91;", ' ', '', '' ], $content ) ) );
	}

}
