<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\Message;
use SMW\NamespaceManager;
use Html;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class ConfigurationListTaskHandler extends TaskHandler {

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
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_SUPPLEMENT;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return true;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'settings';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {
		return Html::rawElement(
			'li',
			array(),
			$this->getMessageAsString(
				array(
					'smw-admin-supplementary-settings-intro',
					$this->outputFormatter->getSpecialPageLinkWith( $this->getMessageAsString( 'smw-admin-supplementary-settings-title' ), array( 'action' => 'settings' ) )
				)
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$this->outputFormatter->setPageTitle(
			$this->getMessageAsString( 'smw-admin-supplementary-settings-title' )
		);

		$this->outputFormatter->addParentLink(
			[ 'tab' => 'supplement' ]
		);

		$this->outputFormatter->addHtml(
			Html::rawElement(
				'p',
				[
					'class' => 'plainlinks'
				],
				$this->getMessageAsString( 'smw-admin-settings-docu', Message::PARSE )
			)
		);

		$options = ApplicationFactory::getInstance()->getSettings()->toArray();

		$this->outputFormatter->addAsPreformattedText(
			str_replace( '\\\\', '\\', $this->outputFormatter->encodeAsJson( $this->cleanPath( $options ) ) )
		);

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson(
				[
					'canonicalNames' => NamespaceManager::getCanonicalNames()
				]
			)
		);
	}

	private function cleanPath( array &$options ) {

		foreach ( $options as $key => &$value ) {
			if ( is_array( $value ) ) {
				$this->cleanPath( $value );
			}

			if ( is_string( $value ) && strpos( $value , 'SemanticMediaWiki/') !== false ) {
				$value = preg_replace('/[\s\S]+?SemanticMediaWiki/', '.../SemanticMediaWiki', $value );
			}
		}

		return $options;
	}

}
