<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use SMW\Store;
use Html;
use WebRequest;
use Title;
use Job;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DisposeJobTaskHandler extends TaskHandler {

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
	 * @var null|Job
	 */
	private $refreshjob = null;

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
		return $task === 'dispose';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		// smw-admin-outdateddisposal
		$this->htmlFormRenderer
				->addHeader( 'h3', $this->getMessageAsString( 'smw-admin-outdateddisposal-title' ) )
				->addParagraph( $this->getMessageAsString( 'smw-admin-outdateddisposal-intro', Message::PARSE ) );

		if ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) && !$this->hasEntityIdDisposerJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'dispose' )
				->addSubmitButton(
					$this->getMessageAsString( 'smw-admin-outdateddisposal-button' ),
					array(
						'class' => ''
					)
				);
		} elseif ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) ) {
			$this->htmlFormRenderer
				->addParagraph(
					Html::element( 'span', array( 'class' => 'smw-admin-circle-orange' ), '' ) .
					Html::element( 'span', array( 'style' => 'font-style:italic; margin-left:25px;' ), $this->getMessageAsString( 'smw-admin-outdateddisposal-active' ) )
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

		if ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) && !$this->hasEntityIdDisposerJob() ) {
			$entityIdDisposerJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
				'SMW\EntityIdDisposerJob',
				\SpecialPage::getTitleFor( 'SMWAdmin' )
			);

			$entityIdDisposerJob->insert();
		}

		$this->outputFormatter->redirectToRootPage( $this->getMessageAsString( 'smw-admin-outdateddisposal-title' ) );
	}

	private function hasEntityIdDisposerJob() {

		if ( !$this->isEnabledFeature( SMW_ADM_DISPOSAL ) ) {
			return false;
		}

		$jobQueueLookup = ApplicationFactory::getInstance()->create(
			'JobQueueLookup',
			$this->store->getConnection( 'mw.db' )
		);

		$row = $jobQueueLookup->selectJobRowBy(
			'SMW\EntityIdDisposerJob'
		);

		return $row !== null && $row !== false;
	}

}
