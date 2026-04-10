<?php

namespace SMW\MediaWiki\Specials\Admin;

use MediaWiki\Html\Html;
use MediaWiki\Request\WebRequest;
use SMW\Utils\HtmlTabs;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class AlertsTaskHandler extends TaskHandler {

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly OutputFormatter $outputFormatter,
		private readonly array $taskHandlers = [],
	) {
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getSection(): string {
		return self::SECTION_ALERTS;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function hasAction(): bool {
		return false;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $action ): bool {
		return false;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getHtml(): string {
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
	public function handleRequest( WebRequest $webRequest ): void {
	}

}
