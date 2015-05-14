<?php

namespace SMW;

use ParamProcessor\ProcessingResult;
use Parser;
use ParserHooks\HookDefinition;
use ParserHooks\HookHandler;
use SMWOutputs;

/**
 * Class that provides the {{#info}} parser function
 *
 * @ingroup ParserFunction
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class InfoParserFunction implements HookHandler {

	/**
	 * @param Parser $parser
	 * @param ProcessingResult $result
	 *
	 * @return mixed
	 */
	public function handle( Parser $parser, ProcessingResult $result ) {
		$parameters = $result->getParameters();

		/**
		 * Non-escaping is safe bacause a user's message is passed through parser, which will
		 * handle unsafe HTM elements.
		 */
		$result = smwfEncodeMessages(
			array( $parameters['message']->getValue() ),
			$parameters['icon']->getValue(),
			' <!--br-->',
			false // No escaping.
		);

		if ( !is_null( $parser->getTitle() ) && $parser->getTitle()->isSpecialPage() ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		}
		else {
			SMWOutputs::commitToParser( $parser );
		}

		return $result;
	}

	public static function getHookDefinition() {
		return new HookDefinition(
			'info',
			array(
				array(
					'name' => 'message',
					'message' => 'smw-info-par-message',
				),
				array(
					'name' => 'icon',
					'message' => 'smw-info-par-icon',
					'default' => 'info',
					'values' => array( 'info', 'warning', 'note' ),
				),
			),
			array(
				'message',
				'icon'
			)
		);
	}

}
