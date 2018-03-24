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
		return $task === 'fulltrebuild';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$subject = DIWikiPage::newFromTitle( \SpecialPage::getTitleFor( 'SMWAdmin' ) );

		if ( $this->isEnabledFeature( SMW_ADM_FULLT ) && !$this->hasPendingJob() ) {
			$this->htmlFormRenderer
				->addHeader( 'h4', $this->getMessageAsString( 'smw-admin-fulltext-title' ) )
				->addParagraph( $this->getMessageAsString( 'smw-admin-fulltext-intro', Message::PARSE ), [ 'class' => 'plainlinks' ] )
				->setMethod( 'post' )
				->addHiddenField( 'action', 'fulltrebuild' )
				->addSubmitButton(
					$this->getMessageAsString( 'smw-admin-fulltext-button' ),
					[
						'class' => $this->isApiTask() ? 'smw-admin-api-job-task' : '',
						'data-job' => 'SMW\FulltextSearchTableRebuildJob',
						'data-subject' => $subject->getHash(),
						'data-parameters' => json_encode( [ 'mode' => '' ] )
					]
				);
		} elseif ( $this->isEnabledFeature( SMW_ADM_FULLT ) ) {
			$this->htmlFormRenderer
				->addHeader( 'h4', $this->getMessageAsString( 'smw-admin-fulltext-title' ) )
				->addParagraph( $this->getMessageAsString( 'smw-admin-fulltext-intro', Message::PARSE ), [ 'class' => 'plainlinks' ] )
				->addParagraph(
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
						$this->getMessageAsString( 'smw-admin-fulltext-active' )
					)
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

		if ( !$this->isEnabledFeature( SMW_ADM_FULLT ) || $this->hasPendingJob() || $this->isApiTask() ) {
			return $this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'rebuild' ] );
		}

		$fulltextSearchTableRebuildJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
			'SMW\FulltextSearchTableRebuildJob',
			\SpecialPage::getTitleFor( 'SMWAdmin' ),
			array(
				'mode' => 'full'
			)
		);

		$fulltextSearchTableRebuildJob->insert();

		$this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'rebuild' ] );
	}

	private function hasPendingJob() {
		return ApplicationFactory::getInstance()->getJobQueue()->hasPendingJob( 'SMW\FulltextSearchTableRebuildJob' );
	}

}
