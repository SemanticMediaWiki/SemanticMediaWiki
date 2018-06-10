<?php

namespace SMW\Query;

use Html;
use ParserOutput;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DeferredQuery {

	/**
	 * Identifies the showMode
	 */
	const SHOW_MODE = 'dq.showmode';

	/**
	 * Identifies unparsed parameters
	 */
	const QUERY_PARAMETERS = 'dq.parameters';

	/**
	 * Identifies the @control element
	 */
	const CONTROL_ELEMENT = 'dq.control';

	/**
	 * @since 3.0
	 *
	 * @param ParserOutput $parserOutput
	 */
	public static function registerResourceModules( ParserOutput $parserOutput ) {
		$parserOutput->addModuleStyles( 'ext.smw.deferred.styles' );
		$parserOutput->addModules( 'ext.smw.deferred' );
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 *
	 * @return string
	 */
	public static function getHtml( Query $query ) {

		$isShowMode = $query->getOption( self::SHOW_MODE );

		// Ensures that a generated string can appear next to another text
		$element = 'span';

		$result = \Html::rawElement(
			$element,
			array(
				'class' => 'smw-deferred-query',
				'data-query'  => trim( $query->getOption( self::QUERY_PARAMETERS ) ),
				'data-limit'  => $query->getLimit(),
				'data-offset' => $query->getOffset(),
				'data-max'    => $GLOBALS['smwgQMaxInlineLimit'],
				'data-cmd'    => $isShowMode ? 'show' : 'ask'
			),
			\Html::rawElement( $element, array(
				'id' => 'deferred-control',
				'data-control' => $isShowMode ? '' : $query->getOption( self::CONTROL_ELEMENT )
			), '' ) .
			\Html::rawElement( $element, array(
				'id' => 'deferred-output',
				'class' => 'smw-loading-image-dots'
			), '' )
		);

		return $result;
	}

}
