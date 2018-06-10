<?php

namespace SMW\Query\ResultPrinters;

use Sanitizer;
use SMW\ApplicationFactory;
use SMWQueryResult as QueryResult;

/**
 * Exports data as file in a format that is defined by its invoked templates.
 * Custom specifications and requirements can be specified freely by relying on
 * the available template system.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TemplateFileExportPrinter extends FileExportPrinter {

	/**
	 * @var integer
	 */
	private $numRows = 0;

	/**
	 * @var TemplateRenderer
	 */
	private $templateRenderer;

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->msg( 'smw_printername_templatefile' )->text();
	}

	/**
	 * @see FileExportPrinter::getMimeType
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getMimeType( QueryResult $queryResult ) {

		if ( $this->params['mimetype'] !== '' ) {
			return $this->params['mimetype'];
		}

		return 'text/plain';
	}

	/**
	 * @see FileExportPrinter::getFileName
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getFileName( QueryResult $queryResult ) {
		return $this->params['filename'];
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['searchlabel']->setDefault( 'templateFile' );

		$params['template arguments'] = [
			'message' => 'smw-paramdesc-template-arguments',
			'default' => 'legacy',
			'values' => [ 'numbered', 'named', 'legacy' ],
		];

		$params['template'] = [
			'type' => 'string',
			'default' => '',
			'message' => 'smw-paramdesc-template',
		];

		$params['valuesep'] = [
			'message' => 'smw-paramdesc-sep',
			'default' => ',',
		];

		$params['userparam'] = [
			'message' => 'smw-paramdesc-userparam',
			'default' => '',
		];

		$params['introtemplate'] = [
			'message' => 'smw-paramdesc-introtemplate',
			'default' => '',
		];

		$params['outrotemplate'] = [
			'message' => 'smw-paramdesc-outrotemplate',
			'default' => '',
		];

		$params['filename'] = [
			'message' => 'smw-paramdesc-filename',
			'default' => 'file.txt',
		];

		$params['mimetype'] = [
			'type' => 'string',
			'message' => 'smw-paramdesc-mimetype',
			'default' => 'text/plain',
		];

		return $params;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {

		// Always return a link for when the output mode is not a file request,
		// a file request is normally only initiated when resolving the query
		// via Special:Ask
		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			return $this->getFileLink( $queryResult, $outputMode );
		}

		$text = $this->expandTemplates(
			$this->getText( $queryResult )
		);

		return trim( $text, "\n" );
	}

	private function getFileLink( QueryResult $queryResult, $outputMode ) {

		// Can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = $outputMode == SMW_OUTPUT_HTML;

		$link = $this->getLink(
			$queryResult,
			$outputMode
		);

		return $link->getText( $outputMode, $this->mLinker );
	}

	private function getText( $queryResult ) {

		$this->templateRenderer = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newWikitextTemplateRenderer();
		$result = '';

		$link = $this->getLink(
			$queryResult,
			SMW_OUTPUT_RAW
		);

		$link = $link->getText( SMW_OUTPUT_RAW, $this->mLinker );

		// Extra fields include:
		// - {{{userparam}}}
		// - {{{querylink}}}

		if ( $this->params['introtemplate'] !== '' ) {
			$this->templateRenderer->addField( 'userparam', $this->params['userparam'] );
			$this->templateRenderer->addField( 'querylink', $link );

			$this->templateRenderer->packFieldsForTemplate(
				$this->params['introtemplate']
			);

			$result .= $this->templateRenderer->render();
		}

		while ( $row = $queryResult->getNext() ) {
			$result .= $this->row( $queryResult, $row );
		}

		// Extra fields include:
		// - {{{userparam}}}
		// - {{{querylink}}}

		if ( $this->params['outrotemplate'] !== '' ) {
			$this->templateRenderer->addField( 'userparam', $this->params['userparam'] );
			$this->templateRenderer->addField( 'querylink', $link );

			$this->templateRenderer->packFieldsForTemplate(
				$this->params['outrotemplate']
			);

			$result .= $this->templateRenderer->render();
		}

		return $result;
	}

	private function row( QueryResult $queryResult, array $row ) {

		$this->numRows + 1;
		$this->addFields( $row );

		$this->templateRenderer->packFieldsForTemplate(
			$this->params['template']
		);

		return $this->templateRenderer->render();
	}

	private function addFields( $row ) {

		foreach ( $row as $i => $field ) {

			$value = '';
			$fieldName = '';

			// {{{?Foo}}}
			if ( $this->params['template arguments'] === 'legacy'  ) {
				$fieldName = '?' . $field->getPrintRequest()->getLabel();
			}

			// {{{Foo}}}
			if ( $this->params['template arguments'] === 'named' ) {
				$fieldName = $field->getPrintRequest()->getLabel();
			}

			// {{{1}}}
			if ( $fieldName === '' || $fieldName === '?' || $this->params['template arguments'] === 'numbered' ) {
				$fieldName = intval( $i + 1 );
			}

			while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $i == 0 ) ) ) !== false ) {
				$value .= $value === '' ? $text : $this->params['valuesep'] . ' ' . $text;
			}

			$this->templateRenderer->addField( $fieldName, $value );
		}

		$this->templateRenderer->addField( '#', $this->numRows );
	}

}
