<?php

namespace SMW\Query\Processor;

use SMW\Query\ResultPrinter;
use SMW\Message;
use ParamProcessor\ParamDefinition;
use SMW\Query\QueryContext;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DefaultParamDefinition {

	/**
	 * Produces a list of allowed parameters of a query using any specific format.
	 *
	 * @since 3.0
	 *
	 * @param integer|null $context
	 * @param ResultPrinter|null $resultPrinter
	 *
	 * @return ParamDefinition[]
	 */
	public static function getParamDefinitions( $context = null, ResultPrinter $resultPrinter = null ) {
		return self::buildParamDefinitions( $GLOBALS, $context, $resultPrinter );
	}

	/**
	 * @private
	 *
	 * Give grep a chance to find the msg usages:
	 *
	 * smw-paramdesc-format, smw-paramdesc-source, smw-paramdesc-limit,
	 * smw-paramdesc-offset, smw-paramdesc-link, smw-paramdesc-sort,
	 * smw-paramdesc-order, smw-paramdesc-headers, smw-paramdesc-mainlabel,
	 * smw-paramdesc-intro, smw-paramdesc-outro, smw-paramdesc-searchlabel,
	 * smw-paramdesc-default
	 *
	 * @since 3.0
	 *
	 * @param integer|null $context
	 * @param ResultPrinter|null $resultPrinter
	 *
	 * @return ParamDefinition[]
	 */
	public static function buildParamDefinitions( $vars, $context = null, ResultPrinter $resultPrinter = null ) {
		$params = [];

		$allowedFormats = $vars['smwgResultFormats'];

		foreach ( $vars['smwgResultAliases'] as $aliases ) {
			$allowedFormats += $aliases;
		}

		$allowedFormats[] = 'auto';

		$params['format'] = [
			// @see $wgParamDefinitions['smwformat']
			'type' => 'smwformat',
			'default' => 'auto',
		];

		// TODO $params['format']->setToLower( true );
		// TODO $allowedFormats

		$params['source'] = self::getSourceParam( $vars );

		$params['limit'] = [
			'type' => 'integer',
			'default' => $vars['smwgQDefaultLimit'],
			'lowerbound' => 0,
		];

		$params['offset'] = [
			'type' => 'integer',
			'default' => 0,
			'lowerbound' => 0,
			'upperbound' => $vars['smwgQUpperbound'],
		];

		$params['link'] = [
			'default' => 'all',
			'values' => [ 'all', 'subject', 'none' ],
		];

		// The empty string represents the page itself, which should be sorted by default.
		$params['sort'] = [
			'islist' => true,
			'default' => [ '' ]
		];

		$params['order'] = [
			'islist' => true,
			'default' => [],
			'values' => [ 'descending', 'desc', 'asc', 'ascending', 'rand', 'random' ],
		];

		$params['headers'] = [
			'default' => 'show',
			'values' => [ 'show', 'hide', 'plain' ],
		];

		$params['mainlabel'] = [
			'default' => false,
		];

		$params['intro'] = [
			'default' => '',
		];

		$params['outro'] = [
			'default' => '',
		];

		$params['searchlabel'] = [
			'default' => Message::get( 'smw_iq_moreresults', Message::TEXT, Message::USER_LANGUAGE )
		];

		$params['default'] = [
			'default' => '',
		];

		if ( $context === QueryContext::DEFERRED_QUERY ) {
			$params['@control'] = [
				'default' => '',
				'values' => [ 'slider' ],
			];
		}

		if ( !( $resultPrinter instanceof ResultPrinter ) || $resultPrinter->supportsRecursiveAnnotation() ) {
			$params['import-annotation'] = [
				'message' => 'smw-paramdesc-import-annotation',
				'type' => 'boolean',
				'default' => false
			];
		}

		foreach ( $params as $name => &$param ) {
			if ( is_array( $param ) ) {
				$param['message'] = 'smw-paramdesc-' . $name;
			}
		}

		return ParamDefinition::getCleanDefinitions( $params );
	}

	private static function getSourceParam( $vars ) {
		$sourceValues = is_array( $vars['smwgQuerySources'] ) ? array_keys( $vars['smwgQuerySources'] ) : [];

		return [
			'default' => array_key_exists( 'default', $sourceValues ) ? 'default' : '',
			'values' => $sourceValues,
		];
	}

}
