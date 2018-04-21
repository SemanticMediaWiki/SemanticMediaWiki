<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;

/**
 * Class TemplateRendererFactory
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Stephan Gambke
 */
class TemplateRendererFactory {

	private $templateRenderer;

	private $queryResult;
	private $numberOfPages;
	private $userparam = '';

	/**
	 * TemplateRendererFactory constructor.
	 *
	 * @param $queryResult
	 */
	public function __construct( $queryResult ) {
		$this->queryResult = $queryResult;
	}

	/**
	 * @param mixed $userparam
	 */
	public function setUserparam( $userparam ) {
		$this->userparam = $userparam;
	}

	/**
	 * @return WikitextTemplateRenderer
	 */
	public function getTemplateRenderer() {

		if ( $this->templateRenderer === null ) {
			$this->templateRenderer = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newWikitextTemplateRenderer();
			$this->addCommonTemplateFields( $this->templateRenderer );
		}

		return clone( $this->templateRenderer );
	}

	/**
	 * @param WikitextTemplateRenderer $templateRenderer
	 */
	private function addCommonTemplateFields( WikitextTemplateRenderer $templateRenderer ) {

		if ( $this->userparam !== '' ) {

			$templateRenderer->addField(
				'#userparam',
				$this->userparam
			);
		}

		$query = $this->getQueryResult()->getQuery();

		$templateRenderer->addField(
			'#querycondition',
			$query->getQueryString()
		);

		$templateRenderer->addField(
			'#querylimit',
			$query->getLimit()
		);

		$templateRenderer->addField(
			'#resultoffset',
			$query->getOffset()
		);

		$templateRenderer->addField(
			'#rowcount',
			$this->getRowCount()
		//$query->getCount()  // FIXME: Re-activate if another query takes too long.
		);
	}

	/**
	 * @return \SMWQueryResult
	 */
	private function getQueryResult() {
		return $this->queryResult;
	}

	/**
	 * @return int
	 */
	private function getRowCount() {

		if ( $this->numberOfPages === null ) {

			$queryResult = $this->getQueryResult();

			$countQuery = \SMWQueryProcessor::createQuery( $queryResult->getQueryString(), \SMWQueryProcessor::getProcessedParams( [] ) );
			$countQuery->querymode = \SMWQuery::MODE_COUNT;

			$countQueryResult = $queryResult->getStore()->getQueryResult( $countQuery );

			$this->numberOfPages = $countQueryResult instanceof \SMWQueryResult ? $countQueryResult->getCountValue() : $countQueryResult;
		}

		return $this->numberOfPages;
	}

}