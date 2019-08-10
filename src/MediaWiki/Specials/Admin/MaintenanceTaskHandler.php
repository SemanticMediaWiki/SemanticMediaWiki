<?php

namespace SMW\MediaWiki\Specials\Admin;

use Html;
use SMW\Message;
use WebRequest;
use SMW\Utils\FileFetcher;
use SMW\Utils\HtmlTabs;

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

		$tasks = '';
		$html = '';

		foreach ( $this->taskHandlers as $key => $taskHandler ) {
			if ( $key == 0 ) {
				$html = $taskHandler->getHtml();
				$tasks .= $this->jobNote();
			}else {
				$tasks .= $taskHandler->getHtml();
			}
		}

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'maintenance' );
		$htmlTabs->setActiveTab( 'tasks' );

		$htmlTabs->tab( 'tasks', $this->msg( 'smw-admin-maintenance-tab-tasks' ) );
		$htmlTabs->content( 'tasks', $tasks );

		$htmlTabs->tab( 'scripts', $this->msg( 'smw-admin-maintenance-tab-scripts' ) );
		$htmlTabs->content( 'scripts', $this->buildHTML() );

		$html .= $htmlTabs->buildHTML( [ 'class' => 'maintenance', 'style' => 'margin-top:20px;' ] );

		$this->outputFormatter->addInlineStyle(
			'.maintenance #tab-tasks:checked ~ #tab-content-tasks,' .
			'.maintenance #tab-scripts:checked ~ #tab-content-scripts {' .
			'display: block;}'
		);

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
		return Html::rawElement(
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
			'p',
			[
				'class' => ''
			],
			$this->msg( 'smw-admin-maintenance-script-section-intro' )
		);

		$this->fileFetcher->sort( 'asc' );

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
			$section = '';

			$description = $this->msg(
				'smw-admin-maintenance-script-description-' . strtolower( str_replace('.php', '', $name ) ),
				Message::PARSE
			);

			if ( strpos( $name, 'update' ) !== false ) {
				$section = 'update';
			} elseif ( strpos( $name, 'rebuild' ) !== false ) {
				$section = 'rebuild';
			}

			$scripts[$section][] = Html::rawElement(
				'a',
				[
					'href' => $this->msg( [ 'smw-helplink', $name ] )
				],
				$name
			) . ":&nbsp;". $description;
		}

		$list = '';

		foreach ( $scripts as $section => $scr ) {

			if ( $section !== '' ) {
				$list .= "<h4>" . $this->msg( "smw-admin-maintenance-script-section-$section" ) . "</h4>";
			}

			$list .= Html::rawElement(
				'ul',
				[
					'class' => 'plainlinks'
				],
				'<li>' . implode( '</li><li>', $scr ) . '</li>'
			);
		}

		$html .= Html::rawElement(
			'div',
			[
				'class' => ''
			],
			$list
		);

		return $html;
	}

}
