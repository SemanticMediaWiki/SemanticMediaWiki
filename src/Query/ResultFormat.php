<?php

namespace SMW\Query;

use ParamProcessor\Definition\StringParam;
use ParamProcessor\IParam;
use ParamProcessor\IParamDefinition;
use SMWQueryProcessor as QueryProcessor;

/**
 * Definition for the format parameter.
 *
 * @license GNU GPL v2+
 * @since 1.6.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ResultFormat extends StringParam {

	/**
	 * List of the queries print requests, used to determine the format
	 * when it's not provided. Set with setPrintRequests before passing
	 * to Validator.
	 *
	 * @since 1.6.2
	 *
	 * @var PrintRequest[]
	 */
	protected $printRequests = [];

	protected $showMode = false;

	/**
	 * Takes a format name, which can be an alias and returns a format name
	 * which will be valid for sure. Aliases are resolved. If the given
	 * format name is invalid, the predefined default format will be returned.
	 *
	 * @since 1.6.2
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function getValidFormatName( $value ) {
		global $smwgResultFormats;

		$value = strtolower( trim( $value ) );

		if ( !array_key_exists( $value, $smwgResultFormats ) ) {
			$isAlias = self::resolveFormatAliases( $value );

			if ( !$isAlias ) {
				$value = $this->getDefaultFormat();
				self::resolveFormatAliases( $value );
			}
		}

		return $value;
	}

	/**
	 * Turns format aliases into main formats.
	 *
	 * @since 1.6.2
	 *
	 * @param string $format
	 *
	 * @return boolean Indicates if the passed format was an alias, and thus was changed.
	 */
	public static function resolveFormatAliases( &$format ) {
		global $smwgResultAliases;

		$isAlias = false;

		foreach ( $smwgResultAliases as $mainFormat => $aliases ) {
			if ( in_array( $format, $aliases ) ) {
				$format = $mainFormat;
				$isAlias = true;
				break;
			}
		}

		return $isAlias;
	}

	/**
	 * Determines and returns the default format, based on the queries print
	 * requests, if provided.
	 *
	 * @since 1.6.2
	 *
	 * @return string Array key in $smwgResultFormats
	 */
	protected function getDefaultFormat() {

		if ( empty( $this->printRequests ) ) {
			return 'table';
		}

		$format = false;

		// Deprecated since 3.1, use `SMW::ResultFormat::OverrideDefaultFormat`
		\Hooks::run( 'SMWResultFormat', [ &$format, $this->printRequests, [] ] );

		/**
		 * This hook allows extensions to override SMWs implementation of default result
		 * format handling.
		 *
		 * @since 3.1
		 */
		\Hooks::run( 'SMW::ResultFormat::OverrideDefaultFormat', [ &$format, $this->printRequests, [] ] );

		if ( $format !== false ) {
			return $format;
		}

		// If no default was set by an extension, use a table, plainlist or list, depending on showMode and column count.
		if ( count( $this->printRequests ) > 1 ) {
			return 'table';
		}

		return 'plainlist';
	}

	/**
	 * Sets the print requests of the query, used for determining
	 * the default format if none is provided.
	 *
	 * @since 1.6.2
	 *
	 * @param PrintRequest[] $printRequests
	 */
	public function setPrintRequests( array $printRequests ) {
		$this->printRequests = $printRequests;
	}

	/**
	 *
	 * @since 3.0
	 *
	 * @param bool $showMode
	 */
	public function setShowMode( $showMode ) {
		$this->showMode = $showMode;
	}

	/**
	 * Formats the parameter value to it's final result.
	 *
	 * @since 1.8
	 *
	 * @param mixed $value
	 * @param IParam $param
	 * @param IParamDefinition[] $definitions
	 * @param IParam[] $params
	 *
	 * @return mixed
	 */
	protected function formatValue( $value, IParam $param, array &$definitions, array $params ) {
		$value = parent::formatValue( $value, $param, $definitions, $params );

		// Make sure the format value is valid.
		$value = self::getValidFormatName( $value );

		// Add the formats parameters to the parameter list.
		$definitions = QueryProcessor::getResultPrinter( $value )->getParamDefinitions(
			$definitions
		);

		return $value;
	}

}
