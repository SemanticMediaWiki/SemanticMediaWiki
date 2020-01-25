<?php

namespace SMW\MediaWiki\Specials\Admin\Maintenance;

use Html;
use SMW\ApplicationFactory;
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
class FulltextSearchTableRebuildJobTaskHandler extends TaskHandler implements ActionableTask {

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
		return 'fulltrebuild';
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

		if ( $this->hasFeature( SMW_ADM_FULLT ) && !$this->hasPendingJob() ) {
			$this->htmlFormRenderer
				->addHeader( 'h4', $this->msg( 'smw-admin-fulltext-title' ) )
				->addParagraph( $this->msg( 'smw-admin-fulltext-intro', Message::PARSE ), [ 'class' => 'plainlinks' ] )
				->setMethod( 'post' )
				->addHiddenField( 'action', 'fulltrebuild' )
				->addSubmitButton(
					$this->msg( 'smw-admin-fulltext-button' ),
					[
						'class' => $this->isApiTask() ? 'smw-admin-api-job-task' : '',
						'data-job' => 'smw.fulltextSearchTableRebuild',
						'data-subject' => $subject->getHash(),
						'data-parameters' => json_encode( [ 'mode' => '' ] )
					]
				);
		} elseif ( $this->hasFeature( SMW_ADM_FULLT ) ) {
			$this->htmlFormRenderer
				->addHeader( 'h4', $this->msg( 'smw-admin-fulltext-title' ) )
				->addParagraph( $this->msg( 'smw-admin-fulltext-intro', Message::PARSE ), [ 'class' => 'plainlinks' ] )
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
						$this->msg( 'smw-admin-fulltext-active' )
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

		if ( !$this->hasFeature( SMW_ADM_FULLT ) || $this->hasPendingJob() || $this->isApiTask() ) {
			return $this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'maintenance' ] );
		}

		$job = ApplicationFactory::getInstance()->newJobFactory()->newByType(
			'smw.fulltextSearchTableRebuild',
			\SpecialPage::getTitleFor( 'SMWAdmin' ),
			[
				'mode' => 'full'
			]
		);

		$job->insert();

		$this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'maintenance' ] );
	}

	private function hasPendingJob() {
		return ApplicationFactory::getInstance()->getJobQueue()->hasPendingJob( 'smw.fulltextSearchTableRebuild' );
	}

}
