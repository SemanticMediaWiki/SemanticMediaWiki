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

		return Html::rawElement( 'div', array( 'class' => 'smw-admin-deprecation-notice-section' ),
				Html::rawElement( 'p', array( 'class' => 'plainlinks' ), $this->getMessageAsString( 'smw-admin-deprecation-notice-docu' ) ) .
				Html::rawElement( 'div', array( 'class' => 'plainlinks' ),
					Html::rawElement( 'p', array(), implode( '', $noticeList ) )
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

		$noticeList = array();
		$list = array();

		foreach ( $noticeConfigList as $setting => $value ) {
			if ( $setting === 'options' ) {
				foreach ( $value as $key => $v ) {
					$this->createListItems( $list, 'smw-admin-deprecation-notice-config-notice', $key, $v );
				}
			} elseif ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createListItem( array( 'smw-admin-deprecation-notice-config-notice', '$' . $setting, $value ) );
			}
		}

		if ( $list !== [] ) {
			$this->createList( $noticeList, $list, 'smw-admin-deprecation-notice-title-notice' );
		}

		foreach ( $replacementConfigList as $setting => $value ) {
			if ( $setting === 'options' ) {
				foreach ( $value as $key => $v ) {
					$this->createListItems( $list, 'smw-admin-deprecation-notice-config-replacement', $key, $v );
				}
			} elseif ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createListItem( array( 'smw-admin-deprecation-notice-config-replacement', '$' . $setting, '$' . $value ) );
			}
		}

		if ( $list !== [] ) {
			$this->createList( $noticeList, $list, 'smw-admin-deprecation-notice-title-replacement' );
		}

		foreach ( $removedConfigList as $setting => $msg ) {
			if ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createListItem( array( 'smw-admin-deprecation-notice-config-removal', '$' . $setting, $msg ) );
			}
		}

		if ( $list !== [] ) {
			$this->createList( $noticeList, $list, 'smw-admin-deprecation-notice-title-removal' );
		}

		return $noticeList;
	}

	private function createList( &$noticeList, &$list, $title ) {

		if ( $list === array() ) {
			return;
		}

		$noticeList[] = Html::rawElement(
			'h3',
			array(),
			$this->getMessageAsString( $title )
		) .	Html::rawElement(
			'ul',
			array(),
			implode( '', $list )
		);

		$list = array();
	}

	private function createListItem( $message ) {
		return Html::rawElement( 'li', array(), $this->getMessageAsString( $message, Message::PARSE ) );
	}

	private function createListItems( &$list, $message, $setting, $options ) {

		$opt = [];

		if ( !is_array( $options ) ) {
			return;
		}

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

}
