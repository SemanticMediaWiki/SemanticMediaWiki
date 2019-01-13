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
class MaintenanceTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var FileFetcher
	 */
	private $fileFetcher;

	/**
	 * @var TaskHandler[]
	 */
	private $taskHandlers = [];

	/**
	 * @since 3.1
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param FileFetcher $fileFetcher
	 * @param TaskHandler[] $taskHandlers
	 */
	public function __construct( OutputFormatter $outputFormatter, FileFetcher $fileFetcher, array $taskHandlers = [] ) {
		$this->outputFormatter = $outputFormatter;
		$this->fileFetcher = $fileFetcher;
		$this->taskHandlers = $taskHandlers;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_MAINTENANCE;
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

		$html = '';

		foreach ( $this->taskHandlers as $key => $taskHandler ) {

			$html .= $taskHandler->getHtml();

			if ( $key == 0 ) {
				$html .= $this->jobNote();
			}
		}

		$html .= $this->buildHTML();

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

	private function jobNote() {
		return  Html::rawElement(
			'hr',
			[
				'class' => 'smw-admin-hr'
			],
			''
		)  . Html::rawElement(
			'p',
			[
				'class' => 'plainlinks',
				'style' => 'margin-top:0.8em;'
			],
			$this->msg( 'smw-admin-job-scheduler-note', Message::PARSE )
		);
	}

	private function buildHTML() {

		$html = Html::rawElement(
			'hr',
			[
				'class' => 'smw-admin-hr'
			],
			''
		) . Html::rawElement(
			'h3',
			[
				'class' => ''
			],
			$this->msg( 'smw-admin-maintenance-script-section-title' )
		) . Html::rawElement(
			'p',
			[
				'class' => ''
			],
			$this->msg( 'smw-admin-maintenance-script-section-intro' )
		);

		$files = $this->fileFetcher->findByExtension( 'php' );
		$scripts = [];

		foreach ( $files as $script ) {
			require_once $script[0];

			// Auto-discover the class name!
			$classes = get_declared_classes();
			$class = end( $classes );

			$mainClass = new $class;

			if ( !$mainClass instanceof \Maintenance ) {
				continue;
			}

			$name = basename( $script[0] );

			$description = $this->msg(
				'smw-admin-maintenance-script-description-' . strtolower( str_replace('.php', '', $name ) ),
				Message::PARSE
			);

			$scripts[] = Html::rawElement(
				'a',
				[
					'href' => $this->msg( [ 'smw-helplink', $name ] )
				],
				$name
			) . ":&nbsp;". $description;
		}

		$list = Html::rawElement(
			'ul',
			[
				'class' => 'plainlinks'
			],
			'<li>' . implode( '</li><li>', $scripts ) . '</li>'
		);

		$html .= Html::rawElement(
			'div',
			[
				'class' => 'smw-column-twofold-responsive'
			],
			$list
		);

		return $html;
	}

}
