<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\SQLStore\Installer;
use SMW\Message;
use SMW\Store;
use Html;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class TableSchemaTaskHandler extends TaskHandler {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( Store $store, HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->store = $store;
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'updatetables';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$this->htmlFormRenderer
			->setName( 'buildtables' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'updatetables' )
			->addHeader( 'h3', $this->getMessageAsString( 'smw-admin-db' ) )
			->addParagraph( $this->getMessageAsString( 'smw-admin-dbdocu' ) );

		if ( $this->isEnabledFeature( SMW_ADM_SETUP ) ) {
			$this->htmlFormRenderer
				->addHiddenField( 'udsure', 'yes' )
				->addSubmitButton(
					$this->getMessageAsString( 'smw-admin-dbbutton' ),
					array(
						'class' => ''
					)
				);
		} else {
			$this->htmlFormRenderer
				->addParagraph( $this->getMessageAsString( 'smw-admin-feature-disabled' ) );
		}

		return Html::rawElement( 'div', array(), $this->htmlFormRenderer->getForm() );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		if ( !$this->isEnabledFeature( SMW_ADM_SETUP ) ) {
			return;
		}

		$this->outputFormatter->setPageTitle( $this->getMessageAsString( 'smw-admin-db' ) );
		$this->outputFormatter->addParentLink( [ 'tab' => 'rebuild' ] );

		$messageReporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();

		$messageReporter->registerReporterCallback(
			[
				$this,
				'reportMessage'
			]
		);

		$this->store->setMessageReporter(
			$messageReporter
		);

		$preparation = $webRequest->getVal( 'prep' );
		$result = false;

		$msg = Html::rawElement(
			'p',
			[],
			$this->getMessageAsString( 'smw-admin-permissionswarn' )
		);

		// Reload (via JS) the page once content is displayed as separate page to inform
		// the user about a possible delay in processing
		if ( $preparation !== 'done' ) {
			$this->outputFormatter->addHTML(
				$msg . Html::rawElement(
					'div',
					[
						'style' => 'opacity:0.5;position: relative;'
					],
					Html::rawElement(
						'pre',
						[
							'class' => 'smw-admin-db-preparation'
						],
						$this->getMessageAsString( 'smw-admin-db-preparation' ) .
						"\n\n" . $this->getMessageAsString( 'smw-processing' ) . "\n" .
						Html::rawElement(
							'span',
							[
								'class' => 'smw-overlay-spinner medium',
								'style' => 'transform: translate(-50%, -50%);'
							]
						)
					)
				)
			);
		} else {
			$this->outputFormatter->addHTML( $msg );
			$this->outputFormatter->addHTML( '<pre>' );
			$this->store->setup();
			$this->outputFormatter->addHTML( '</pre>' );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->outputFormatter->addHTML( $message );
	}

}
