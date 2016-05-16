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

	// Highlighter ID for no types
	const TYPE_NOTYPE    = 0;
	// Highlighter ID for properties
	const TYPE_PROPERTY  = 1;
	// Highlighter ID for text
	const TYPE_TEXT      = 2;
	// Highlighter ID for quantities
	const TYPE_QUANTITY  = 3;
	//  Highlighter ID for warnings
	const TYPE_WARNING   = 4;
	//  Highlighter ID for informations
	const TYPE_INFO      = 5;
	//  Highlighter ID for help
	const TYPE_HELP      = 6;
	//  Highlighter ID for notes
	const TYPE_NOTE      = 7;
	//  Highlighter ID for service links
	const TYPE_SERVICE   = 8;

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
			case 'info':
			return self::TYPE_INFO;
			case 'help':
			return self::TYPE_HELP;
			case 'note':
			return self::TYPE_NOTE;
			case 'service':
			return self::TYPE_SERVICE;
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

		return Html::rawElement(
			'span',
			array(
				'class'      => 'smw-highlighter',
				'data-type'  => $this->options['type'],
				'data-state' => $this->options['state'],
				'data-title' => Message::get( $this->options['title'], Message::TEXT, $language ),
			), Html::rawElement(
					'span',
					array(
						'class' => $captionclass
					), $this->options['caption']
				) . Html::rawElement(
					'div',
					array(
						'class' => 'smwttcontent'
					), $this->options['content']
				)
			);
	}

	/**
	 * Returns initial configuation settings
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
		$settings = array();
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
			case self::TYPE_SERVICE:
				$settings['state'] = 'persistent';
				$settings['title'] = 'smw-ui-tooltip-title-service';
				$settings['captionclass'] = 'smwtticon service';
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
}
