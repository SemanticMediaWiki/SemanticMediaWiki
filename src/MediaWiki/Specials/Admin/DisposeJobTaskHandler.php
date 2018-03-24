<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use SMW\DIWikiPage;
use SMW\Store;
use Html;
use WebRequest;
use Title;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DisposeJobTaskHandler extends TaskHandler {

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var null|Job
	 */
	private $refreshjob = null;

	/**
	 * @var boolean
	 */
	public $isApiTask = true;

	/**
	 * @since 2.5
	 *
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_DATAREPAIR;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isApiTask() {
		return $this->isApiTask;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'dispose';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$subject = DIWikiPage::newFromTitle( \SpecialPage::getTitleFor( 'SMWAdmin' ) );

		// smw-admin-outdateddisposal
		$this->htmlFormRenderer
				->addHeader( 'h4', $this->getMessageAsString( 'smw-admin-outdateddisposal-title' ) )
				->addParagraph(
					$this->getMessageAsString( 'smw-admin-outdateddisposal-intro', Message::PARSE ),
					[
						'id' => 'smw-admin-outdated-disposal',
						'class' => 'plainlinks'
					]
				);

		if ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) && !$this->hasPendingJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'dispose' )
				->addSubmitButton(
					$this->getMessageAsString( 'smw-admin-outdateddisposal-button' ),
					[
						'class' => $this->isApiTask() ? 'smw-admin-api-job-task' : '',
						'data-job' => 'SMW\EntityIdDisposerJob',
						'data-subject' => $subject->getHash()
					]
				);
		} elseif ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) ) {
			$this->htmlFormRenderer->addParagraph(
					Html::element(
						'span',
						[
							'class' => 'smw-admin-circle-orange'
						]
					) . Html::element(
						'span',
						[
							'style' => 'font-style:italic; margin-left:25px;'
						],
						$this->getMessageAsString( 'smw-admin-outdateddisposal-active' )
					),
					[ 'id' => 'smw-admin-outdated-disposal-status' ]
				);
		} else {
			$this->htmlFormRenderer->addParagraph(
				$this->getMessageAsString( 'smw-admin-feature-disabled' )
			);
		}

		return Html::rawElement(
			'div',
			[],
			$this->htmlFormRenderer->getForm()
		);
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		if ( !$this->isEnabledFeature( SMW_ADM_DISPOSAL ) || $this->hasPendingJob() || $this->isApiTask() ) {
			return $this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'rebuild' ] );
		}

		$entityIdDisposerJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
			'SMW\EntityIdDisposerJob',
			\SpecialPage::getTitleFor( 'SMWAdmin' )
		);

		$entityIdDisposerJob->insert();

		$this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'rebuild' ] );
	}

	private function hasPendingJob() {
		return ApplicationFactory::getInstance()->getJobQueue()->hasPendingJob( 'SMW\EntityIdDisposerJob' );
	}

}
