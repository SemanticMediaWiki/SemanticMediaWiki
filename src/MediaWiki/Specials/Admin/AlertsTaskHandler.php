<?php

namespace SMW\MediaWiki\Specials\Admin;

use Html;
use SMW\Message;
use WebRequest;
use SMW\Utils\HtmlTabs;

/**
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class AlertsTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var TaskHandler[]
	 */
	private $taskHandlers = [];

	/**
	 * @since 3.2
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param TaskHandler[] $taskHandlers
	 */
	public function __construct( OutputFormatter $outputFormatter, array $taskHandlers = [] ) {
		$this->outputFormatter = $outputFormatter;
		$this->taskHandlers = $taskHandlers;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_ALERTS;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return false;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $action ) {
		return false;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$html = '';
		$tabs = [];

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'alerts' );

		foreach ( $this->taskHandlers as $key => $taskHandler ) {
			$name = $taskHandler->getName();
			$content = $taskHandler->getHtml();

			$tabs[] = $name;

			$htmlTabs->tab(
				$name,
				$this->msg( "smw-admin-alerts-tab-$name" ),
				[
					'hide'  => $content === '',
					'class' => 'smw-tab-notice'
				]
			);

			$htmlTabs->content( $name, $content );
		}

		if ( !$htmlTabs->hasContents() ) {
			return '';
		}

		$html = Html::rawElement(
			'p',
			[
				'class' => ''
			],
			$this->msg( 'smw-admin-alerts-section-intro' )
		);

		$html .= $htmlTabs->buildHTML(
			[
				'class' => 'alerts'
			]
		);

		$inlineStyles = [];

		foreach ( $tabs as $tabName ) {
			$inlineStyles[] = ".alerts #tab-$tabName:checked ~ #tab-content-$tabName";
		}

		$this->outputFormatter->addInlineStyle( implode( ',', $inlineStyles ) . ' {display: block;}' );

		return $html;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {}

}
