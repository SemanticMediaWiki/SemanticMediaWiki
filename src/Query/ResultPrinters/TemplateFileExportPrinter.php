<?php

namespace SMW\Query\ResultPrinters;

use Sanitizer;
use SMW\ApplicationFactory;
use SMWQueryResult as QueryResult;
use SMW\MediaWiki\Template\Template;
use SMW\MediaWiki\Template\TemplateSet;
use SMW\MediaWiki\Template\TemplateExpander;

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

		$params['valuesep'] = [
			'message' => 'smw-paramdesc-sep',
			'default' => ',',
		];

		$params['template'] = [
			'type' => 'string',
			'default' => '',
			'message' => 'smw-paramdesc-template',
		];

		$params['named args'] =  [
			'type' => 'boolean',
			'message' => 'smw-paramdesc-named_args',
			'default' => false,
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

		$templateExpander = new TemplateExpander(
			$this->copyParser()
		);

		$text = $templateExpander->expand(
			$this->newTemplateSet( $queryResult )
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

	private function newTemplateSet( $queryResult ) {

		$templateSet = new TemplateSet();

		$link = $this->getLink(
			$queryResult,
			SMW_OUTPUT_RAW
		);

		$link = $link->getText( SMW_OUTPUT_RAW, $this->mLinker );

		if ( $this->params['introtemplate'] !== '' ) {
			$template = new Template(
				$this->params['introtemplate']
			);

			$template->field( '#userparam', $this->params['userparam'] );
			$template->field( '#querylink', $link );
			$templateSet->addTemplate( $template );
		}

		while ( $row = $queryResult->getNext() ) {
			$template = new Template(
				$this->params['template']
			);

			$template->field( '#userparam', $this->params['userparam'] );
			$this->addFields( $template, $row );
			$templateSet->addTemplate( $template );
		}

		if ( $this->params['outrotemplate'] !== '' ) {
			$template = new Template(
				$this->params['outrotemplate']
			);

			$template->field( '#userparam', $this->params['userparam'] );
			$template->field( '#querylink', $link );
			$templateSet->addTemplate( $template );
		}

		return $templateSet;
	}

	private function addFields( $template, array $row ) {
		$this->numRows + 1;

		foreach ( $row as $i => $field ) {

			$value = '';
			$fieldName = '';

			// {{{Foo}}}
			if ( $this->params['named args'] === true ) {
				$fieldName = $field->getPrintRequest()->getLabel();
			}

			// {{{1}}}
			if ( $fieldName === '' || $fieldName === '?' ) {
				$fieldName = intval( $i + 1 );
			}

			while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $i == 0 ) ) ) !== false ) {
				$value .= $value === '' ? $text : $this->params['valuesep'] . ' ' . $text;
			}

			$template->field( $fieldName, $value );
		}

		$template->field( '#', $this->numRows );
	}

}
