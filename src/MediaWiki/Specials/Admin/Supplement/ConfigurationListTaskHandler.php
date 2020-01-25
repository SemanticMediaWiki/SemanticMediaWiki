<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\ApplicationFactory;
use SMW\Message;
use SMW\NamespaceManager;
use WebRequest;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\Utils\HtmlTabs;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class ConfigurationListTaskHandler extends TaskHandler implements ActionableTask {

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
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getTask() : string {
		return 'settings';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( string $action ) : bool {
		return $action === $this->getTask();
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-settings-title' ),
			[
				'action' => $this->getTask()
			]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-settings-intro',
					$link
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

		$this->outputFormatter->setPageTitle(
			$this->msg( [ 'smw-admin-main-title', $this->msg( 'smw-admin-supplementary-settings-title' ) ] )
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
				$this->msg( 'smw-admin-settings-docu', Message::PARSE )
			)
		);

		$options = ApplicationFactory::getInstance()->getSettings()->toArray();

		$placeholder = Html::rawElement(
			'div',
			[
				'class' => 'smw-json-placeholder-message',
			],
			Message::get( 'smw-data-lookup-with-wait' ) .
			"\n\n\n" . Message::get( 'smw-preparing' ) . "\n"
		) .	Html::rawElement(
			'span',
			[
				'class' => 'smw-overlay-spinner medium',
				'style' => 'transform: translate(-50%, -50%);'
			]
		);

		$html = Html::rawElement(
				'div',
				[
					'id' => 'smw-admin-configutation-json',
					'class' => '',
				],
				Html::rawElement(
					'div',
					[
						'class' => 'smw-jsonview-menu',
					]
				) . Html::rawElement(
					'pre',
					[
						'id' => 'smw-json-container',
						'class' => 'smw-json-container smw-json-placeholder',
					],
					$placeholder . Html::rawElement(
						'div',
						[
							'class' => 'smw-json-data'
						],
						$this->outputFormatter->encodeAsJson( $this->cleanPath( $options )
					)
				)
			)
		);

		$namespaces = $this->outputFormatter->encodeAsJson(
			[
				'canonicalNames' => NamespaceManager::getCanonicalNames()
			]
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'configuration' );
		$htmlTabs->setActiveTab( 'settings' );

		$htmlTabs->tab( 'settings', $this->msg( 'smw-admin-configutation-tab-settings' ) );
		$htmlTabs->content( 'settings', $html );

		$htmlTabs->tab( 'namespaces', $this->msg( 'smw-admin-configutation-tab-namespaces' ) );
		$htmlTabs->content( 'namespaces', Html::rawElement( 'pre', [], $namespaces ) );

		$html = $htmlTabs->buildHTML( [ 'class' => 'configuration' ] );

		$this->outputFormatter->addHtml( $html );

		$this->outputFormatter->addInlineStyle(
			'.configuration #tab-settings:checked ~ #tab-content-settings,' .
			'.configuration #tab-namespaces:checked ~ #tab-content-namespaces {' .
			'display: block;}'
		);
	}

	private function cleanPath( array &$options ) {

		foreach ( $options as $key => &$value ) {
			if ( is_array( $value ) ) {
				$this->cleanPath( $value );
			}

			if ( is_string( $value ) && strpos( $value , 'SemanticMediaWiki/') !== false ) {
				$value = preg_replace('/[\s\S]+?SemanticMediaWiki/', '../SemanticMediaWiki', $value );
			}
		}

		return $options;
	}

}
