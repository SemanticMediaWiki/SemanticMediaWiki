<?php

namespace SMW\MediaWiki\Specials\Admin;

use Html;
use SMW\Message;
use WebRequest;
use SMW\Utils\FileFetcher;

/**
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class SupplementTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var TaskHandler[]
	 */
	private $taskHandlers = [];

	/**
	 * @since 3.1
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param TaskHandler[] $taskHandlers
	 */
	public function __construct( OutputFormatter $outputFormatter, array $taskHandlers = [] ) {
		$this->outputFormatter = $outputFormatter;
		$this->taskHandlers = $taskHandlers;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_SUPPLEMENT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return true;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $action ) {

		foreach ( $this->taskHandlers as $taskHandler ) {
			if ( $taskHandler->isTaskFor( $action ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$html = $this->buildHTML();
		$list = '';

		foreach ( $this->taskHandlers as $key => $taskHandler ) {
			$list .= $taskHandler->getHtml();
		}

		$html .= Html::rawElement( 'ul', [], $list );

		return $html;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$action = $webRequest->getText( 'action' );

		foreach ( $this->taskHandlers as $taskHandler ) {

			if ( !$taskHandler->isTaskFor( $action ) ) {
				continue;
			}

			$taskHandler->setStore(
				$this->getStore()
			);

			return $taskHandler->handleRequest( $webRequest );
		}
	}

	private function buildHTML() {

		$html = Html::rawElement(
			'p',
			[
				'class' => 'plainlinks'
			],
			$this->msg( 'smw-admin-supplementary-section-intro', Message::PARSE )
		) . Html::rawElement(
			'h3',
			[],
			$this->msg( 'smw-admin-supplementary-section-subtitle' )
		);

		return $html;
	}

}
