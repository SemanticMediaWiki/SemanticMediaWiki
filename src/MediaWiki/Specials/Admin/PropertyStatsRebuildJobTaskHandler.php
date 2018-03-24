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
class PropertyStatsRebuildJobTaskHandler extends TaskHandler {

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

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
		return $task === 'pstatsrebuild';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$subject = DIWikiPage::newFromTitle( \SpecialPage::getTitleFor( 'SMWAdmin' ) );

		// smw-admin-propertystatistics
		$this->htmlFormRenderer
				->addHeader( 'h4', $this->getMessageAsString( 'smw-admin-propertystatistics-title' ) )
				->addParagraph( $this->getMessageAsString( 'smw-admin-propertystatistics-intro', Message::PARSE ), [ 'class' => 'plainlinks' ] );

		if ( $this->isEnabledFeature( SMW_ADM_PSTATS ) && !$this->hasPendingJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'pstatsrebuild' )
				->addSubmitButton(
					$this->getMessageAsString( 'smw-admin-propertystatistics-button' ),
					[
						'class' => $this->isApiTask() ? 'smw-admin-api-job-task' : '',
						'data-job' => 'SMW\PropertyStatisticsRebuildJob',
						'data-subject' => $subject->getHash()
					]
				 );
		} elseif ( $this->isEnabledFeature( SMW_ADM_PSTATS ) ) {
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
						$this->getMessageAsString( 'smw-admin-propertystatistics-active' )
					)
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

		if ( !$this->isEnabledFeature( SMW_ADM_PSTATS ) || $this->hasPendingJob() || $this->isApiTask() ) {
			return $this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'rebuild' ] );
		}

		$propertyStatisticsRebuildJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
			'SMW\PropertyStatisticsRebuildJob',
			\SpecialPage::getTitleFor( 'SMWAdmin' )
		);

		$propertyStatisticsRebuildJob->insert();

		$this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'rebuild' ] );
	}

	private function hasPendingJob() {
		return ApplicationFactory::getInstance()->getJobQueue()->hasPendingJob( 'SMW\PropertyStatisticsRebuildJob' );
	}

}
