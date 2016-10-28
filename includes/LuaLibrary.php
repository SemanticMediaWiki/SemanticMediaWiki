<?php

namespace SMW;

/**
 * Registers all smw lua functions and provides their parser function equivalent.
 *
 * Implements functionality for smw's parser functions ask, info, set, subobject
 *
 * @since 2.5
 * @author Tobias Oetterer
 * @ingroup SMW
 */
class LuaLibrary extends \Scribunto_LuaLibraryBase {

	/**
	 * This string holds the index within $parser->getOutput()->getExtensionData,
	 * where \SMW\LuaLibrary signals the LuaResultPrinter, that we are in lua mode
	 *
	 * @var string
	 */
	const EXTENSION_DATA_LUA_ENV = 'smw_lua_environment_flag';

	/**
	 * This string holds the index, where the LuaResult Printer stores its data in $parser->getOutput()->getExtensionData
	 *
	 * @var string
	 */
	const EXTENSION_DATA_LUA_RESULT = 'smw_lua_result_data';

	/**
	 * This is the string, you have to set your #ask-query format to, to return a data array in lua
	 *
	 * Warning: This must not be set to a value, already defined for a result printer in $smwgResultFormats
	 * If so, SMWExternalHooks::addLuaLibrary cannot register it and no native result data is available in lua
	 *
	 * @var string
	 */
	const LUA_RESULT_FORMAT='lua';

	/**
	 * This is the name of the key for error messages
	 *
	 * @var string
	 */
	const SMW_ERROR_FIELD='error';


	/**
	 * registers available methods as lua functions
	 * Note: \Scribunto_LuaLibraryBase requires this to be defined
	 *
	 * @uses \Scribunto_LuaLibraryBase::getEngine
	 *
	 * @return array
	 */
	public function register()
	{
		$lib = array(
			'ask' => array( $this, 'ask' ),
			'getPropertyCanonicalLabel' => array( $this, 'getPropertyCanonicalLabel' ),
			'getPropertyLabel' => array( $this, 'getPropertyLabel' ),
			'getPropertyType' => array( $this, 'getPropertyType' ),
			'info' => array( $this, 'info' ),
			'set' => array( $this, 'set' ),
			'subobject' => array( $this, 'subobject' ),
		);
		$setupOptions = array(); # these parameters are passed to the package's setupInterface function
		return $this->getEngine()->registerInterface( __DIR__ . '/../lua/smw.lua', $lib, $setupOptions );
	}


	/**
	 * This mirrors the functionality of the parser function #ask and makes it available to lua.
	 *
	 * @global \Parser $wgParser
	 * @global int $smwgQMaxInlineLimit
	 *
	 * @param string|array	$parameters	parameters passed from lua, string or array depending on call
	 *
	 * @uses \SMW\ParserFunctionFactory::__construct, \SMW\ApplicationFactory::getInstance
	 * @uses \SMW\LuaLibrary::LUA_RESULT_FORMAT, \SMW\LuaLibrary::EXTENSION_DATA_LUA_ENV,
	 * @uses \SMW\LuaLibrary::EXTENSION_DATA_LUA_RESULT, \SMW\LuaLibrary::SMW_ERROR_FIELD
	 * @uses \SMW\LuaLibrary::extractErrorMessage
	 *
	 * @return array[]|string[]
	 */
	public function ask( $parameters )
	{
		global $wgParser, $smwgQMaxInlineLimit;

		$result = null;

		# make sure, we have an array of parameters
		if ( !is_array($parameters) ) {
			$parameters = array($parameters);
		}

		# lua starts arrays with 1, but smw expects parameters 0 to be the query; shift everything
		array_unshift($parameters, array_shift($parameters));

		# we have to "inject" some parameters
		if ( !isset( $parameters["format"] ) ) {
			$parameters["format"] = self::LUA_RESULT_FORMAT;
		}
		if ( !isset( $parameters["limit"] ) ) {
			$parameters["limit"] = $smwgQMaxInlineLimit;
		}

		# prepare askParserFunction object
		$parserFunctionFactory = new ParserFunctionFactory( $wgParser );
		$askParserFunction = $parserFunctionFactory->newAskParserFunction( $wgParser );

		# check, if smw is enabled
		$smwgQEnabled = ApplicationFactory::getInstance()->getSettings()->get( 'smwgQEnabled' );

		if ( !$smwgQEnabled ) {
			# smw disabled, return an error
			$messageFormatter = new MessageFormatter( $wgParser->getTargetLanguage() );
			$result = array( self::SMW_ERROR_FIELD => $messageFormatter->addFromKey( 'smw_iq_disabled' )->getPlain() );
		} else {
			# smw available, proceed normally

			if ( isset($parameters["format"]) && $parameters["format"] == self::LUA_RESULT_FORMAT ) {
				# signal the \SMW\LuaResultPrinter::getResultText, that we are in lua environment
				# so that it knows, it is safe to return a data array
				$wgParser->getOutput()->setExtensionData(self::EXTENSION_DATA_LUA_ENV, true);

				# call the ask query parser function object's method parse
				$parserFunctionCallResult = $askParserFunction->parse( $parameters );

				# reset the lua environment signal
				$wgParser->getOutput()->setExtensionData(self::EXTENSION_DATA_LUA_ENV, false);

				# a native lua result was requested
				if ( ! strlen($parserFunctionCallResult) ) {
					# there were no errors in the query, because normally, the lua result formatter returns an empty string
					# get the result from \ParserOutput::getExtensionData
					$result = $wgParser->getOutput()->getExtensionData( self::EXTENSION_DATA_LUA_RESULT );
				} else {
					# something went wrong with the query.
					$result = array( self::SMW_ERROR_FIELD => $this->extractErrorMessage($parserFunctionCallResult) );
				}

				# null the result store
				$wgParser->getOutput()->setExtensionData( self::EXTENSION_DATA_LUA_RESULT, null );
			} else {
				# another format was requested. return the result normally.

				# call the ask query
				$parserFunctionCallResult = $askParserFunction->parse( $parameters );

				# note: this means, the return string can contain an html formatted error. we don't know
				$result = $parserFunctionCallResult;	# note: result is a string, not an array
			}
		}

		# this has to be done, because lua starts tables (aka arrays) by the count of 1, not 0
		if ( is_array($result) && sizeof($result) ) {
			array_unshift($result, null);
		}
		return array( $result );
	}


	/**
	 * Takes the name of a property and returns the canonical label, if any
	 *
	 * @param string $propertyName name of the property
	 *
	 * @uses \SMW\LuaLibrary::getPropertyAttribute
	 *
	 * @return string[]
	 */
	public function getPropertyCanonicalLabel( $propertyName )
	{
		return $this->getPropertyAttribute( $propertyName, 'canonical_label' );
	}

	/**
	 * Takes the name of a property and returns the label, if any
	 *
	 * @param string $propertyName name of the property
	 *
	 * @uses \SMW\LuaLibrary::getPropertyAttribute
	 *
	 * @return string[]
	 */
	public function getPropertyLabel( $propertyName )
	{
		return $this->getPropertyAttribute( $propertyName, 'label' );
	}


	/**
	 * Takes the name of a property and returns the smw type descriptor for it (e.g. _wpg, _num, etc)
	 *
	 * @param string $propertyName name of the property
	 *
	 * @uses \SMW\LuaLibrary::getPropertyAttribute
	 *
	 * @return string[]
	 */
	public function getPropertyType( $propertyName )
	{
		return $this->getPropertyAttribute( $propertyName, 'type' );
	}


	/**
	 * This mirrors the functionality of the parser function #info and makes it available to lua.
	 *
	 * @global \Parser $wgParser
	 *
	 * @param string	$text	text to show inside the info popup
	 * @param string    $icon   identifier for the icon to use
	 *
	 * @uses \SMW\ParserFunctionFactory::__construct, \SMW\LuaLibrary::extractErrorMessage
	 *
	 * @return string[]
	 */
	public function info( $text, $icon=null )
	{
		global $wgParser;

		$result = null;

		# build parameters array
		# note: #info expects parameter 0 to be the text in plain format.
		# however, if $icon is submitted it must be of a class that implements PPNode
		$preProcessor = $wgParser->getPreprocessor();
		$parameters = array( 0 => $text );
		$parameters[] = $preProcessor->newPartNodeArray( [ 1 => $icon ] )->item( 0 );

		# prepare infoParserFunction object
		$infoFunctionDefinition = \SMW\ParserFunctions\InfoParserFunction::getHookDefinition();
		$infoFunctionHandler = new \SMW\ParserFunctions\InfoParserFunction();
		$infoFunctionRunner = new \ParserHooks\FunctionRunner( $infoFunctionDefinition, $infoFunctionHandler );

		# get ourselves a usable frame
		$pFrame = new \PPFrame_DOM( $preProcessor );

		# run the thing
		$parserFunctionCallResult = $infoFunctionRunner->run( $wgParser, $parameters, $pFrame );

		# result is an array with maybe 'noparse' set to false. check for this
		$noParse = ( is_array($parserFunctionCallResult) && isset($parserFunctionCallResult['noparse']) )
			? $parserFunctionCallResult['noparse'] : true;
		$result = is_array($parserFunctionCallResult) ? $parserFunctionCallResult[0] : $parserFunctionCallResult;

		if ( ! $noParse ) {
			$result = $wgParser->recursiveTagParseFully( $result );
		}

		return array( $result );
	}


	/**
	 * This mirrors the functionality of the parser function #set and makes it available to lua.
	 *
	 * @global \Parser $wgParser
	 *
	 * @param string|array	$parameters	parameters passed from lua, string or array depending on call
	 *
	 * @uses \SMW\ParserFunctionFactory::__construct, ParameterProcessorFactory::newFromArray
	 * @uses \SMW\LuaLibrary::extractErrorMessage
	 *
	 * @return array[]|string[]
	 */
	public function set( $parameters )
	{
		global $wgParser;

		$result = null;

		# make sure, we have an array of parameters
		if ( !is_array($parameters) ) {
			$parameters = array($parameters);
		}

		# lua starts arrays with 1, so shift everything
		array_unshift($parameters, array_shift($parameters));

		# prepare setParserFunction object
		$parserFunctionFactory = new ParserFunctionFactory( $wgParser );
		$setParserFunction = $parserFunctionFactory->newSetParserFunction( $wgParser );

		$parserFunctionCallResult = $setParserFunction->parse(
			ParameterProcessorFactory::newFromArray( $parameters )
		);

		if ( is_array($parserFunctionCallResult) ) {
			$result = $parserFunctionCallResult[0];
			$noParse = isset($parserFunctionCallResult['noparse']) ? $parserFunctionCallResult['noparse'] : true;
			$isHtml = isset($parserFunctionCallResult['isHTML']) ? $parserFunctionCallResult['isHTML'] : false;
		} else {
			$result = $parserFunctionCallResult;
			$noParse = true;
			$isHtml = false;
		}

		if ( ! $noParse ) {
			$result = $wgParser->recursiveTagParseFully( $result );
		}
		$result = trim($result);

		# if this a non empty string, assume an error message
		if ( strlen ($result) ) {
			$result = $this->extractErrorMessage($result);
		}

		return array( $result );
	}


	/**
	 * This mirrors the functionality of the parser function #subobject and makes it available to lua.
	 *
	 * @global \Parser $wgParser
	 *
	 * @param string|array	$parameters	parameters passed from lua, string or array depending on call
	 *
	 * @uses \SMW\ParserFunctionFactory::__construc,  \SMW\ParameterProcessorFactory::newFromArray
	 *
	 * @return string[]|null
	 */
	public function subobject( $parameters, $subobjectId = null )
	{
		global $wgParser;

		$result = null;

		# make sure, we have an array of parameters
		if ( !is_array($parameters) ) {
			$parameters = array($parameters);
		}

		# lua starts arrays with 1, which is great, because parameters[0] would be the subobject id
		if ( isset($parameters[0]) ) {
			array_unshift($parameters, null);
		}

		# if suboject id was set, put it on position 0
		if ( !is_null($subobjectId) && $subobjectId ) {
			# user deliberately set an id for this subobject
			$parameters[0] = $subobjectId;

			# we need to ksort, otherwise ParameterProcessorFactory doesn't recognize the id
			ksort($parameters);
		}

		# prepare subobjectParserFunction object
		$parserFunctionFactory = new ParserFunctionFactory( $wgParser );
		$subobjectParserFunction = $parserFunctionFactory->newSubobjectParserFunction( $wgParser );

		# preprocess the parameters for the subobject
		$processedParameter = ParameterProcessorFactory::newFromArray( $parameters );

		$parserFunctionCallResult = $subobjectParserFunction->parse( $processedParameter );

		if ( is_string($parserFunctionCallResult) && strlen($parserFunctionCallResult) ) {
			# this will most probably indicate an error
			return array( $parserFunctionCallResult );
		} else {
			return array( null );
		}
	}


	/**
	 * Takes the name of a property and returns the desired $attribute for it
	 *
	 * @global $wgCapitalLinks
	 *
	 * @param string $propertyName name of the property
	 * @param string $attribute the attribute to return
	 *
	 * @uses \SMW\DIProperty::__construct
	 *
	 * @return string[]
	 */
	private function getPropertyAttribute( $propertyName, $attribute )
	{
		global $wgCapitalLinks;

		# pre process the property name
		if ( is_array($propertyName) ) {
			$propertyName = array_shift($propertyName);
		}

		if ( !is_string($propertyName) ) {
			return '';
		}

		$propertyName = trim($propertyName);

		# propertyName must contain neither namespaces
		if ( preg_match('~.*:([^:]+)$~', $propertyName, $matches) ) {
			$propertyName = $matches[1];
		}
		# no spaces
		$propertyName = str_replace(' ', '_', $propertyName);

		# if $wgCapitalLinks is set to true (default) ucfirst the propertyName
		# this is for your convenience, in case you have accidently given it all lowercase
		if ( $wgCapitalLinks ) {
			$propertyName = ucfirst($propertyName);
		}

		try {
			# build a property object
			$property = new DIProperty( $propertyName );

			$attribute = strtolower($attribute);
			switch ( $attribute ) {
				case 'canonical_label' :
					$result = $property->getCanonicalLabel();
					break;

				case 'label' :
					$result = $property->getLabel();
					break;

				case 'type' :
					$result = $property->findPropertyTypeID();
					break;

				default :
					$result = __METHOD__ . ' [internal error]: no property attribute specified!';
					break;
			}
		}
		catch ( \Exception $e ) {
			$result = $e->getMessage();
		}

		if ( is_array($result) && sizeof($result) ) {
			array_unshift($result, null);
		}

		return array( $result );
	}


	/**
	 * Takes a string, looks for a smw error message and returns it, if found.
	 * If not, return original string instead
	 *
	 * @param string	$text	text to parse for error message
	 *
	 * @return string
	 */
	private function extractErrorMessage( $text )
	{
		if ( preg_match('~^.*smw-highlighter.*smwttcontent.*>(.*)</div>.*$~', $text, $matches) ) {
			# looks like we have a formatted error string
			return $matches[0];
		} else {
			# fallback to returning whole string
			return $text;
		}
	}
}
