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

		$this->outputFormatter->setPageTitle( $this->getMessageAsString( 'smw-admin-supplementary-settings-title' ) );
		$this->outputFormatter->addParentLink();

		$this->outputFormatter->addHtml(
			Html::rawElement( 'p', array(), $this->getMessageAsString( 'smw-admin-settings-docu', Message::PARSE ) )
		);

		$this->outputFormatter->addHtml(
			'<pre>' . $this->outputFormatter->encodeAsJson( ApplicationFactory::getInstance()->getSettings()->getOptions() ) . '</pre>'
		);

		$this->outputFormatter->addHtml(
			'<pre>' . $this->outputFormatter->encodeAsJson( array( 'canonicalNames' => NamespaceManager::getCanonicalNames() ) ) . '</pre>'
		);
	}

}
