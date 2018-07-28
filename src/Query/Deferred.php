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
class Deferred {

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
	public static function registerResources( ParserOutput $parserOutput ) {
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
	public static function buildHTML( Query $query ) {

		$isShowMode = $query->getOption( self::SHOW_MODE );
		$params = $query->getOption( 'query.params' );

		// Ensures that a generated string can appear next to another text
		$element = $isShowMode ? 'span' : 'div';

		$result = Html::rawElement(
			$element,
			[
				'class' => 'smw-deferred-query' . ( isset( $params['class'] ) ? ' ' . $params['class'] : '' ),
				'data-query'  => json_encode(
					[
						'query'  => trim( $query->getOption( self::QUERY_PARAMETERS ) ),
						'params' => $params,
						'limit'  => $query->getLimit(),
						'offset' => $query->getOffset(),
						'max'    => $GLOBALS['smwgQMaxInlineLimit'],
						'cmd'    => $isShowMode ? 'show' : 'ask'
					]
				)
			],
			Html::rawElement(
				$element,
				[
					'id' => 'deferred-control',
					'data-control' => $isShowMode ? '' : $query->getOption( self::CONTROL_ELEMENT )
				]
			) . Html::rawElement(
				$element,
				[
					'id' => 'deferred-output',
					'class' => 'smw-loading-image-dots'
				]
			)
		);

		return $result;
	}

}
