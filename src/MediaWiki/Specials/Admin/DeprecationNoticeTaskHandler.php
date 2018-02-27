<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\Message;
use Html;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class DeprecationNoticeTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var array
	 */
	private $deprecationNoticeList = array();

	/**
	 * @since 3.0
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param array $deprecationNoticeList
	 */
	public function __construct( OutputFormatter $outputFormatter, array $deprecationNoticeList = array() ) {
		$this->outputFormatter = $outputFormatter;
		$this->deprecationNoticeList = $deprecationNoticeList;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_DEPRECATION;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$noticeConfigList = array();
		$replacementConfigList = array();
		$removedConfigList = array();

		if ( isset( $this->deprecationNoticeList['notice'] ) ) {
			$noticeConfigList = $this->deprecationNoticeList['notice'];
		}

		if ( isset( $this->deprecationNoticeList['replacement'] ) ) {
			$replacementConfigList = $this->deprecationNoticeList['replacement'];
		}

		if ( isset( $this->deprecationNoticeList['removal'] ) ) {
			$removedConfigList = $this->deprecationNoticeList['removal'];
		}

		$noticeList = $this->detectOn(
			$noticeConfigList,
			$replacementConfigList,
			$removedConfigList
		);

		if ( $noticeList === array() ) {
			return '';
		}

		return Html::rawElement(
			'div',
			array(
				'class' => 'smw-admin-deprecation-notice-section'
			),
			Html::rawElement(
				'p',
				array(
					'class' => 'plainlinks'
				),
				$this->getMessageAsString( 'smw-admin-deprecation-notice-docu' )
			) . Html::rawElement(
					'div',
					array(
						'class' => 'plainlinks'
				),
				Html::rawElement(
					'p',
					[],
					implode( '', $noticeList )
				)
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {}

	private function detectOn( $noticeConfigList, $replacementConfigList, $removedConfigList ) {

		$noticeList = [];
		$list = array();

		// Replacements
		foreach ( $replacementConfigList as $setting => $value ) {
			if ( $setting === 'options' ) {
				$list[] = $this->createListItems( 'smw-admin-deprecation-notice-config-replacement', $value );
			} elseif ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createListItem( array( 'smw-admin-deprecation-notice-config-replacement', '$' . $setting, '$' . $value ) );
			}
		}

		if ( $list !== [] && ( $mList = $this->mergeList( 'smw-admin-deprecation-notice-title-replacement', $list ) ) !== null ) {
			$noticeList[] = $mList;
		}

		// Changes
		foreach ( $noticeConfigList as $setting => $value ) {
			if ( $setting === 'options' ) {
				$list[] = $this->createListItems( 'smw-admin-deprecation-notice-config-notice', $value );
			} elseif ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createListItem( array( 'smw-admin-deprecation-notice-config-notice', '$' . $setting, $value ) );
			}
		}

		if ( $list !== [] && ( $mList = $this->mergeList( 'smw-admin-deprecation-notice-title-notice', $list ) ) !== null ) {
			$noticeList[] = $mList;
		}

		// Removals
		foreach ( $removedConfigList as $setting => $msg ) {
			if ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createListItem( array( 'smw-admin-deprecation-notice-config-removal', '$' . $setting, $msg ) );
			}
		}

		if ( $list !== [] && ( $mList = $this->mergeList( 'smw-admin-deprecation-notice-title-removal', $list ) ) !== null ) {
			$noticeList[] = $mList;
		}

		return $noticeList;
	}

	private function mergeList( $title, &$list ) {

		if ( $list === array() || ( $items = implode( '', $list ) ) === '' ) {
			return;
		}

		$html = Html::rawElement(
			'h3',
			[],
			$this->getMessageAsString( $title )
		) . Html::rawElement(
			'p',
			[
				'class' => 'smw-admin-deprecation-notice-section-explanation',
				'style' => 'margin-bottom:10px;'
			],
			$this->getMessageAsString( $title . '-explanation' )
		) . Html::rawElement(
			'ul',
			[
				'style' => 'margin-bottom:10px;'
			],
			$items
		);

		$list = [];

		return $html;
	}

	private function createListItem( $message ) {
		return Html::rawElement( 'li', array(), $this->getMessageAsString( $message, Message::PARSE ) );
	}

	private function createListItems( $message, $values ) {

		$list = [];

		if ( !is_array( $values ) ) {
			return '';
		}

		foreach ( $values as $setting => $options ) {

			if ( !is_array( $options ) ) {
				continue;
			}

			$opt = [];

			foreach ( $options as $option => $v ) {
				if ( isset( $GLOBALS[$setting][$option] ) ) {
					$opt[] = $this->createListItem(
						[
							$message . '-option-list',
							$option,
							$v
						]
					);
				}
			}

			if ( $opt !== [] ) {
				$list[] = $this->createListItem(
					[
						$message . '-option',
						'$' . $setting,
						count( $opt )
					]
				) . '<ul>' . implode( '', $opt ) . '</ul>';
			}
		}

		return implode( '', $list );
	}

}
