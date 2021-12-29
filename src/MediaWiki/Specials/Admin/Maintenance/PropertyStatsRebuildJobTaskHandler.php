<?php

namespace SMW\MediaWiki\Specials\Admin\Maintenance;

use Html;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\Message;
use Title;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class PropertyStatsRebuildJobTaskHandler extends TaskHandler implements ActionableTask {

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
		return self::SECTION_MAINTENANCE;
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
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getTask() : string {
		return 'pstatsrebuild';
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

		$subject = DIWikiPage::newFromTitle( \SpecialPage::getTitleFor( 'SMWAdmin' ) );

		// smw-admin-propertystatistics
		$this->htmlFormRenderer
				->addHeader( 'h4', $this->msg( 'smw-admin-propertystatistics-title' ) )
				->addParagraph( $this->msg( 'smw-admin-propertystatistics-intro', Message::PARSE ), [ 'class' => 'plainlinks' ] );

		if ( $this->hasFeature( SMW_ADM_PSTATS ) && !$this->hasPendingJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', $this->getTask() )
				->addSubmitButton(
					$this->msg( 'smw-admin-propertystatistics-button' ),
					[
						'class' => $this->isApiTask() ? 'smw-admin-api-job-task' : '',
						'data-job' => 'smw.propertyStatisticsRebuild',
						'data-subject' => $subject->getHash()
					]
				);
		} elseif ( $this->hasFeature( SMW_ADM_PSTATS ) ) {
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
						$this->msg( 'smw-admin-propertystatistics-active' )
					)
				);
		} else {
			$this->htmlFormRenderer->addParagraph(
				$this->msg( 'smw-admin-feature-disabled' )
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

		if ( !$this->hasFeature( SMW_ADM_PSTATS ) || $this->hasPendingJob() || $this->isApiTask() ) {
			return $this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'maintenance' ] );
		}

		$job = ApplicationFactory::getInstance()->newJobFactory()->newByType(
			'smw.propertyStatisticsRebuild',
			\SpecialPage::getTitleFor( 'SMWAdmin' )
		);

		$job->insert();

		$this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'maintenance' ] );
	}

	private function hasPendingJob() {
		return ApplicationFactory::getInstance()->getJobQueue()->hasPendingJob( 'smw.propertyStatisticsRebuild' );
	}

}
