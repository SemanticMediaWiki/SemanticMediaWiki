<?php

/**
 * Class for the 'info' parser functions.
 * 
 * @since 1.5.3
 * 
 * @file SMW_Info.php
 * @ingroup SMW
 * @ingroup ParserHooks
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWInfo extends ParserHook {
	
	/**
	 * Renders and returns the output.
	 * @see ParserHook::render
	 * 
	 * @since 1.7
	 * 
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public function render( array $parameters ) {		
		/**
		 * Non-escaping is safe bacause a user's message is passed through parser, which will
		 * handle unsafe HTM elements.
		 */
		$result = smwfEncodeMessages(
			array( $parameters['message'] ),
			$parameters['icon'],
			' <!--br-->',
			false // No escaping.
		);

		if ( !is_null( $this->parser->getTitle() ) && $this->parser->getTitle()->isSpecialPage() ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		}
		else {
			SMWOutputs::commitToParser( $this->parser );
		}
		
		return $result;		
	}
	
	/**
	 * No LSB in pre-5.3 PHP *sigh*.
	 * This is to be refactored as soon as php >=5.3 becomes acceptable.
	 */	
	public static function staticInit( Parser &$parser ) {
		$instance = new self;
		return $instance->init( $parser );
	}	
	
	/**
	 * Gets the name of the parser hook.
	 * @see ParserHook::getName
	 * 
	 * @since 1.7
	 * 
	 * @return string
	 */
	protected function getName() {
		return 'info';
	}
	
	/**
	 * Returns the list of default parameters.
	 * @see ParserHook::getDefaultParameters
	 * 
	 * @since 1.6
	 * 
	 * @return array
	 */
	protected function getDefaultParameters( $type ) {
		return array( 'message', 'icon' );
	}
	
	/**
	 * Returns an array containing the parameter info.
	 * @see ParserHook::getParameterInfo
	 * 
	 * @since 1.7
	 * 
	 * @return array
	 */
	protected function getParameterInfo( $type ) {
		return array(
			array(
				'name' => 'message',
				'message' => 'smw-info-par-message',
			),
			array(
				'name' => 'icon',
				'message' => 'smw-info-par-icon',
				'default' => 'info',
				'values' => array( 'info', 'warning' ),
			),
		);
	}
	
}
