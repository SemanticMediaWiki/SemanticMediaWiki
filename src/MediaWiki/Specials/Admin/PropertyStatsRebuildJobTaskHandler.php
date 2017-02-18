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
class PropertyStatsRebuildJobTaskHandler extends TaskHandler {

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
		return $task === 'pstatsrebuild';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		// smw-admin-propertystatistics
		$this->htmlFormRenderer
				->addHeader( 'h3', $this->getMessageAsString( 'smw-admin-propertystatistics-title' ) )
				->addParagraph( $this->getMessageAsString( 'smw-admin-propertystatistics-intro', Message::PARSE ) );

		if ( $this->isEnabledFeature( SMW_ADM_PSTATS ) && !$this->hasPropertyStatisticsRebuildJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'pstatsrebuild' )
				->addSubmitButton(
					$this->getMessageAsString( 'smw-admin-propertystatistics-button' ),
					array(
						'class' => ''
					)
				 );
		} elseif ( $this->isEnabledFeature( SMW_ADM_PSTATS ) ) {
			$this->htmlFormRenderer
				->addParagraph(
					Html::element( 'span', array( 'class' => 'smw-admin-circle-orange' ), '' ) .
					Html::element( 'span', array( 'style' => 'font-style:italic; margin-left:25px;' ), $this->getMessageAsString( 'smw-admin-propertystatistics-active' ) )
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

		if ( $this->isEnabledFeature( SMW_ADM_PSTATS ) && !$this->hasPropertyStatisticsRebuildJob() ) {
			$propertyStatisticsRebuildJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
				'SMW\PropertyStatisticsRebuildJob',
				\SpecialPage::getTitleFor( 'SMWAdmin' )
			);

			$propertyStatisticsRebuildJob->insert();
		}

		$this->outputFormatter->redirectToRootPage( $this->getMessageAsString( 'smw-admin-propertystatistics-title' ) );
	}

	private function hasPropertyStatisticsRebuildJob() {

		if ( !$this->isEnabledFeature( SMW_ADM_PSTATS ) ) {
			return false;
		}

		$jobQueueLookup = ApplicationFactory::getInstance()->create(
			'JobQueueLookup',
			$this->store->getConnection( 'mw.db' )
		);

		$row = $jobQueueLookup->selectJobRowBy(
			'SMW\PropertyStatisticsRebuildJob'
		);

		return $row !== null && $row !== false;
	}

}
