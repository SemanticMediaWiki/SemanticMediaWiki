<?php

namespace SMW;

use \SMWQueryResult;
use \SMWResultArray;
use \SMW\ResultPrinter;

/**
 * Simply stores the result of a query as data array in \ParserOutput::setExtensionData
 *
 * Note: since this result format is added by the ScribuntoExternalLibraries hook, which is only
 * called in lua context, this result printer is normally not available.
 * The loophole is when someone tries to call this result printer inside a module via
 * frame:callParserFunction{}. The result printer, however, detects, when not called
 * by \SMW\LuaLibrary and issues an error
 *
 * @since 2.5
 * @author Tobias Oetterer
 * @ingroup QueryPrinter
 */
class LuaResultPrinter extends ResultPrinter {

	/**
	 * This accesses the query result and stores it in a multi dimensional data field in
	 * ParserOutput's ExtensionDataStore
	 *
	 * @global \Parser $wgParser;
	 *
	 * @param \SMWQueryResult $queryResult
	 * @param $outputMode
	 *
	 * @see \SMW\ResultPrinter::getResultText
	 *
	 * @uses \SMW\LuaLibrary::EXTENSION_DATA_LUA_ENV, \SMW\LuaLibrary::LUA_RESULT_FORMAT
	 * @uses wfMessage, \SMW\LuaLibrary::EXTENSION_DATA_LUA_RESULT
	 *
	 * @return string
	 */
	protected function getResultText( \SMWQueryResult $queryResult, $outputMode ) {

		global $wgParser;
		$result = array();

		# this result printer should/can/must only be used in lua environment
		# \SMW\LuaLibrary leaves a flag, to indicate this.
		# If flag is not present, or false, abort with error
		$luaEnvFlag = $wgParser->getOutput()->getExtensionData( \SMW\LuaLibrary::EXTENSION_DATA_LUA_ENV );
		if ( !is_bool($luaEnvFlag) || !$luaEnvFlag ) {
			$queryResult->addErrors( array(
				$this->msg( 'smw-luaresultprinter-error', \SMW\LuaLibrary::LUA_RESULT_FORMAT )->inContentLanguage()->text()
			) );
		} else {
			/** @var \SMWResultArray[] $row */
			while ( $row = $queryResult->getNext() ) {
				$result[] = $this->extractData($row);
			}
			$queryResult->reset();

			# lua counting starts with 1, but we defer the un-shifting to the calling method in LuaLibrary
			$wgParser->getOutput()->setExtensionData( \SMW\LuaLibrary::EXTENSION_DATA_LUA_RESULT, $result );
		}
		return '';
	}

	/**
	 * Extracts semantic data from a single row of a query result.
	 *
	 * @param \SMWResultArray[] $row	array of \SMWResultArray objects
	 *
	 * @return array
	 */
	protected function extractData( $row ) {

		$result = array();
		$numberFallBack = 1;
		$msgTrue = explode(',', wfMessage( 'smw_true_words' )->text() . ',true,t,yes,y');
		/** @var \SMWResultArray $field */
		foreach ( $row as $field ) {
			if ( $field->getPrintRequest()->getLabel() === '' ) {
				$key = $numberFallBack;
				$numberFallBack++;
			} else {
				$key = $field->getPrintRequest()->getText( SMW_OUTPUT_WIKI );
			}
			$result[$key] = array();
			/** @var \SMWDataValue $dataValue */
			while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {
				switch ( $dataValue->getTypeID() ) {
					case '_boo':
						# boolean value found, convert it
						$value = in_array( $dataValue->getShortText( SMW_OUTPUT_WIKI ), $msgTrue );
						break;
					case '_num':
						# number value found
						# FIXME: breaks on float?
						$value = intval( $dataValue->getShortText( SMW_OUTPUT_WIKI ) );
						break;
					default:
						$value = $dataValue->getShortText( SMW_OUTPUT_WIKI );
				}
				$result[$key][] = $value;
				#$result[$key][] = Sanitizer::stripAllTags( $value );
			}
			if ( !$result[$key] || sizeof($result[$key]) == 0 ) {
				# this key has no value(s). set to null
				$result[$key] = null;
			} elseif ( sizeof($result[$key]) == 1 ) {
				# there was only one semantic value found. reduce the array to this value and keep it for $key
				$result[$key] = array_shift($result[$key]);
			} else {
				# $key has multiple values. keep the array, but unshift it (remember: lua counting starts with 1)
				array_unshift( $result[$key], null );
			}
		}
		return $result;
	}

	/**
	 * @see \SMW\ResultPrinter::getParameters
	 *
	 */
	public function getParameters() {
		return array();
	}

	/**
	 * @see \SMW\ResultPrinter::getParamDefinitions
	 *
	 */
	public function getParamDefinitions( array $definitions ) {
	    return array_merge( $definitions, $this->getParameters() );
	}

	/**
	 * @see \SMW\ResultPrinter::handleParameters
	 *
	 */
	protected function handleParameters( array $params, $outputMode ) {
	    // unsused for now
	}

	/**
	 * @see \SMW\ResultPrinter::getName()
	 *
	 */
	public function getName() {
		return "Native Result used to access a query result in lua";
	}
}
