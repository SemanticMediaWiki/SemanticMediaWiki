<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
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
class FulltextSearchTableRebuildJobTaskHandler extends TaskHandler {

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
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'fulltrebuild';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		// smw-admin-fulltext
		$this->htmlFormRenderer
				->addHeader( 'h3', $this->getMessageAsString( 'smw-admin-fulltext-title' ) )
				->addParagraph( $this->getMessageAsString( 'smw-admin-fulltext-intro', Message::PARSE ) );

		if ( $this->isEnabledFeature( SMW_ADM_FULLT ) && !$this->hasPendingFulltextSearchTableRebuildJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'fulltrebuild' )
				->addSubmitButton(
					$this->getMessageAsString( 'smw-admin-fulltext-button' ),
					array(
						'class' => ''
					)
				);
		} elseif ( $this->isEnabledFeature( SMW_ADM_FULLT ) ) {
			$this->htmlFormRenderer
				->addParagraph(
					Html::element( 'span', array( 'class' => 'smw-admin-circle-orange' ), '' ) .
					Html::element( 'span', array( 'style' => 'font-style:italic; margin-left:25px;' ), $this->getMessageAsString( 'smw-admin-fulltext-active' ) )
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

		if ( !$this->isEnabledFeature( SMW_ADM_FULLT ) || $this->hasPendingFulltextSearchTableRebuildJob() ) {
			return false;
		}

		$fulltextSearchTableRebuildJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
			'SMW\FulltextSearchTableRebuildJob',
			\SpecialPage::getTitleFor( 'SMWAdmin' ),
			array(
				'mode' => 'full'
			)
		);

		$fulltextSearchTableRebuildJob->insert();

		$this->outputFormatter->redirectToRootPage( $this->getMessageAsString( 'smw-admin-fulltext-title' ) );
	}

	private function hasPendingFulltextSearchTableRebuildJob() {
		return ApplicationFactory::getInstance()->getJobQueue()->hasPendingJob( 'SMW\FulltextSearchTableRebuildJob' );
	}

}
