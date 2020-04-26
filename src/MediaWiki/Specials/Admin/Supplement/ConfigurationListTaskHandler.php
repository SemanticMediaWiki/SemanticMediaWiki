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
use SMW\Utils\JsonView;

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

		$applicationFactory = ApplicationFactory::getInstance();
		$options = $applicationFactory->getSettings()->toArray();

		$settings = ( new JsonView() )->create(
			'settings',
			$this->outputFormatter->encodeAsJson( $this->cleanPath( $options ) ),
			2
		);

		$namespaces = ( new JsonView() )->create(
			'namespaces',
			$this->outputFormatter->encodeAsJson( [ 'canonicalNames' => NamespaceManager::getCanonicalNames() ] ),
			2
		);

		$schemaTypes = $applicationFactory->singleton( 'SchemaFactory' )->getSchemaTypes();
		$schemaTypes = json_decode( $schemaTypes->jsonSerialize(), true );

		$schematypes = ( new JsonView() )->create(
			'schematypes',
			$this->outputFormatter->encodeAsJson( $this->cleanPath( $schemaTypes ) ),
			2
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'configuration' );
		$htmlTabs->setActiveTab( 'settings' );

		$htmlTabs->tab( 'settings', $this->msg( 'smw-admin-configutation-tab-settings' ) );
		$htmlTabs->content( 'settings', $settings );

		$htmlTabs->tab( 'namespaces', $this->msg( 'smw-admin-configutation-tab-namespaces' ) );
		$htmlTabs->content( 'namespaces', $namespaces );

		$htmlTabs->tab( 'schematypes', $this->msg( 'smw-admin-configutation-tab-schematypes' ) );
		$htmlTabs->content( 'schematypes', $schematypes );

		$html = $htmlTabs->buildHTML( [ 'class' => 'configuration' ] );

		$this->outputFormatter->addHtml( $html );

		$this->outputFormatter->addInlineStyle(
			'.configuration #tab-settings:checked ~ #tab-content-settings,' .
			'.configuration #tab-schematypes:checked ~ #tab-content-schematypes,' .
			'.configuration #tab-namespaces:checked ~ #tab-content-namespaces {' .
			'display: block;}'
		);
	}

	private function cleanPath( array &$options ) {

		foreach ( $options as $key => &$value ) {
			if ( is_array( $value ) ) {
				$this->cleanPath( $value );
			}

			if ( is_string( $value ) && strpos( $value, 'SemanticMediaWiki/' ) !== false ) {
				$value = preg_replace( '/[\s\S]+?SemanticMediaWiki/', '../SemanticMediaWiki', $value );
			}

			if ( is_string( $value ) && strpos( $value, '\\SemanticMediaWiki' ) !== false ) {
				$value = preg_replace( '/[\s\S]+?SemanticMediaWiki/', '..\SemanticMediaWiki', $value );
			}
		}

		return $options;
	}

}
