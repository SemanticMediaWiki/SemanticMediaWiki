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
			[],
			$this->getMessageAsString(
				[
					'smw-admin-supplementary-settings-intro',
					$this->outputFormatter->getSpecialPageLinkWith( $this->getMessageAsString( 'smw-admin-supplementary-settings-title' ), [ 'action' => 'settings' ] )
				]
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
			Html::rawElement( 'p', [], $this->getMessageAsString( 'smw-admin-settings-docu', Message::PARSE ) )
		);

		$this->outputFormatter->addHtml(
			'<pre>' . $this->outputFormatter->encodeAsJson( ApplicationFactory::getInstance()->getSettings()->getOptions() ) . '</pre>'
		);

		$this->outputFormatter->addHtml(
			'<pre>' . $this->outputFormatter->encodeAsJson( [ 'canonicalNames' => NamespaceManager::getCanonicalNames() ] ) . '</pre>'
		);
	}

}
