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
	private $configList = array();

	/**
	 * @since 3.0
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param array $configList
	 */
	public function __construct( OutputFormatter $outputFormatter, array $configList = array() ) {
		$this->outputFormatter = $outputFormatter;
		$this->configList = $configList;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$noticeList = array();

		$deprecatedConfigList = array(
		//	'smwgCacheType' => 'smwgMainCacheType',
			'smwgAdminRefreshStore' => 'smwgAdminFeatures'
		);

		$removedConfigList = array(
		//	'smwgTranslate'
		);

		foreach ( $deprecatedConfigList as $deprecated => $new ) {
			if ( isset( $this->configList[$deprecated] ) ) {
				$noticeList[] = $this->createListItem( array( 'smw-admin-deprecation-notice-config-replacement', '$' . $deprecated, '$' . $new ) );
			}
		}

		foreach ( $removedConfigList as $removed ) {
			if ( isset( $this->configList[$removed] ) ) {
				$noticeList[] = $this->createListItem( array( 'smw-admin-deprecation-notice-config-removal', '$' . $removed ) );
			}
		}

		if ( $noticeList === array() ) {
			return '';
		}

		return Html::rawElement( 'h2', array(), $this->getMessageAsString( 'smw-admin-deprecation-notice-title' ) ) .
			Html::rawElement( 'div', array( 'class' => 'smw-admin-deprecation-notice-section' ),
				Html::rawElement( 'p', array( 'class' => 'smw-admin-deprecation-notice-docu-section plainlinks' ), $this->getMessageAsString( 'smw-admin-deprecation-notice-docu' ) ) .
				Html::rawElement( 'div', array( 'class' => 'plainlinks' ),
					Html::rawElement( 'ul', array(), implode( '', $noticeList ) )
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

	private function createListItem( $message ) {
		return Html::rawElement( 'li', array(), $this->getMessageAsString( $message, Message::PARSE ) );
	}

}
