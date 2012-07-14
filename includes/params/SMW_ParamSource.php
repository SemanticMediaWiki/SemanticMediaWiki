<?php

/**
 * Definition for the source parameter.
 *
 * @since 1.8
 *
 * @file
 * @ingroup SMW
 * @ingroup ParamDefinition
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWParamSource extends StringParam {

	/**
	 * @see ParamDefinition::postConstruct
	 *
	 * @since 1.8
	 */
	protected function postConstruct() {
		parent::postConstruct();

		$this->allowedValues = array_keys( $GLOBALS['smwgQuerySources'] );
		$this->setDefault( array_key_exists( 'default', $this->allowedValues ) ? 'default' : '' );
		$this->setMessage( 'smw-paramdesc-source' );
	}

	/**
	 * @see ParamDefinition::formatValue
	 *
	 * @since 1.8
	 *
	 * @param $value mixed
	 * @param $param IParam
	 * @param $definitions array of IParamDefinition
	 * @param $params array of IParam
	 *
	 * @return SMWStore
	 */
	protected function formatValue( $value, IParam $param, array &$definitions, array $params ) {
		$source = parent::formatValue( $value, $param, $definitions, $params );

		return $source === '' ? smwfGetStore() : new $GLOBALS['smwgQuerySources'][$source]();
	}

}
