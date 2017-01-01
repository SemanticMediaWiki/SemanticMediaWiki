<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\Message;
use Html;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class SupplementaryLinksWidget {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 2.5
	 *
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( OutputFormatter $outputFormatter ) {
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getForm() {
		$html =	Html::rawElement( 'h2', array(), $this->getMessage( array( 'smw-admin-supplementary-section-title' ) ) );
		$html .= Html::rawElement( 'p', array(), $this->getMessage( array( 'smw-admin-supplementary-section-intro' ) ) );
		$html .= Html::rawElement( 'div', array( 'class' => 'smw-admin-supplementary-linksection' ),
			Html::rawElement( 'ul', array(),
				Html::rawElement(
					'li',
					array(),
					$this->getMessage( array( 'smw-admin-supplementary-operational-statistics-intro', $this->outputFormatter->getSpecialPageLinkWith( $this->getMessage( 'smw-admin-supplementary-operational-statistics-title' ), array( 'action' => 'stats' ) ) ) )
				) .
				Html::rawElement(
					'li',
					array(),
					$this->getMessage( array( 'smw-admin-supplementary-settings-intro', $this->outputFormatter->getSpecialPageLinkWith( $this->getMessage( 'smw-admin-supplementary-settings-title' ), array( 'action' => 'settings' ) ) ) )
				) .
				Html::rawElement(
					'li',
					array(),
					$this->getMessage( array( 'smw-admin-supplementary-idlookup-intro', $this->outputFormatter->getSpecialPageLinkWith( $this->getMessage( 'smw-admin-supplementary-idlookup-title' ), array( 'action' => 'idlookup' ) ) ) )
				)
			)
		);

		return Html::rawElement( 'div', array(), $html );
	}

	private function getMessage( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
