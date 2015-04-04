<?php

namespace SMW;

use SMWOutputs;
use IContextSource;
use ContextSource;
use Html;

/**
 * Highlighter utility function for Semantic MediaWiki
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 *
 * @ingroup SMW
 */
class Highlighter extends ContextSource {

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
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param int $type
	 * @param \IContextSource|null $context
	 */
	public function __construct( $type, IContextSource $context = null ) {
		if ( !$context ) {
			$context = \RequestContext::getMain();
		}
		$this->setContext( $context );
		$this->type = $type;
	}

	/**
	 * Factory method
	 *
	 * @since 1.9
	 *
	 * @param string|int $type
	 * @param \IContextSource|null $context
	 *
	 * @return Highlighter
	 */
	public static function factory( $type, IContextSource $context = null ) {
		if ( $type === '' || !is_int( $type ) ) {
			$type = self::getTypeId( $type );
		}

		return new Highlighter( $type, $context );
	}

	/**
	 * Returns html output
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getHtml() {
		//@todo Introduce temporary fix, for more information see bug 43205
		SMWOutputs::requireResource( 'ext.smw.tooltips' );
		// $this->getOutput()->addModules( 'ext.smw.tooltips' );
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
		return Html::rawElement(
			'span',
			array(
				'class'      => 'smw-highlighter',
				'data-type'  => $this->options['type'],
				'data-state' => $this->options['state'],
				'data-title' => $this->msg( $this->options['title'] )->text(),
			), Html::rawElement(
					'span',
					array(
						'class' => $this->options['captionclass']
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
