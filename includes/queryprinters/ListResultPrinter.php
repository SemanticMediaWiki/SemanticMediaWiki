<?php

namespace SMW;

use Html;
use ParamProcessor\ParamDefinition;
use Sanitizer;
use SMWDataItem;
use SMWQueryResult;
use SMWResultArray;

/**
 * Print query results in lists.
 *
 * @author Markus Krötzsch
 * @ingroup SMWQuery
 */

/**
 * New implementation of SMW's printer for results in lists.
 * The implementation covers comma-separated lists, ordered and unordered lists.
 * List items may be formatted using templates, and list output can be in
 * multiple columns (at least for ordered and unordered lists).
 *
 * In the code below, one list item (with all extra information displayed for
 * it) is called a "row", while one entry in this row is called a "field" to
 * avoid confusion with the "columns" that we have in multi-column display.
 * Every field may in turn contain many "values".
 *
 * @ingroup SMWQuery
 */
class ListResultPrinter extends ResultPrinter {

	/**
	 * Get a human readable label for this printer. The default is to
	 * return just the format identifier. Concrete implementations may
	 * refer to messages here. The format name is normally not used in
	 * wiki text but only in forms etc. hence the user language should be
	 * used when retrieving messages.
	 *
	 */
	public function getName() {
		// Give grep a chance to find the usages:
		// smw_printername_list, smw_printername_ol,smw_printername_ul, smw_printername_template
		return Message::decode( 'smw_printername_' . $this->mFormat );
	}

	/**
	 * @see ResultPrinter::isDeferrable
	 *
	 * {@inheritDoc}
	 */
	public function isDeferrable() {
		return true;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * @param SMWQueryResult $queryResult
	 * @param $outputMode
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $queryResult, $outputMode ) {

		$time = microtime( true );

		$builder = new ListResultBuilder( $queryResult, $this->mLinker );
		$builder->set( $this->params );

		$str = $builder->getResultText() .
			$this->getFurtherResultsText( $queryResult, $outputMode );

		// FIXME: Ask the ListBuilder
		$this->hasTemplates = $builder->hasTemplates();

		$time = microtime( true ) - $time;

		return $time . $str;

		// FIXME: This should be taken care of by ResultPrinter, right? Right?
		//// Display default if the result is empty
		//if ( $result == '' ) {
		//	$result = $this->params[ 'default' ];
		//}
	}

	/**
	 * Get text for further results link. Used only during getResultText().
	 *
	 * @since 1.9
	 * @param SMWQueryResult $res
	 * @param integer $outputMode
	 * @return string
	 */
	protected function getFurtherResultsText( SMWQueryResult $res, $outputMode ) {
		$link = $this->getFurtherResultsLink( $res, $outputMode );
		return $link->getText( SMW_OUTPUT_WIKI, $this->mLinker );
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function supportsRecursiveAnnotation() {
		return true;
	}

	/**
	 * @see SMWIResultPrinter::getParamDefinitions
	 *
	 * @since 3.0
	 *
	 * @param ParamDefinition[] $definitions
	 *
	 * @return ParamDefinition[]
	 * @throws \Exception
	 */
	public function getParamDefinitions( array $definitions ) {

		$listFormatDefinitions = [

			'propsep' => [
				'message' => 'smw-paramdesc-propsep',
				'default' => ', ',
			],

			'valuesep' => [
				'message' => 'smw-paramdesc-valuesep',
				'default' => ', ',
			],

			'template' => [
				'message' => 'smw-paramdesc-template',
				'default' => '',
				'trim' => true,
			],

			'named args' => [
				'type' => 'boolean',
				'message' => 'smw-paramdesc-named_args',
				'default' => false,
			],

			'userparam' => [
				'message' => 'smw-paramdesc-userparam',
				'default' => '',
			],

			'introtemplate' => [
				'message' => 'smw-paramdesc-introtemplate',
				'default' => '',
			],

			'outrotemplate' => [
				'message' => 'smw-paramdesc-outrotemplate',
				'default' => '',
			],

		];

		if ( $this->mFormat !== 'ul' && $this->mFormat !== 'ol' ) {

			$listFormatDefinitions[ 'sep' ] =
				[
					'message' => 'smw-paramdesc-sep',
					'default' => ', ',
				];
		}

		$listFormatDefinitions = ParamDefinition::getCleanDefinitions( $listFormatDefinitions );

		return array_merge( $definitions, $listFormatDefinitions );
	}
}
