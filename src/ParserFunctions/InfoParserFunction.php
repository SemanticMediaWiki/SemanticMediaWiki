<?php

namespace SMW\ParserFunctions;

use ParamProcessor\ProcessingError;
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
		if ( $result->hasFatal() ) {
			return $this->getOutputForErrors( $result->getErrors() );
		}

		$parameters = $result->getParameters();

		if ( !isset( $parameters['message'] ) ) {
			return '';
		}

		$message = $parser->mStripState ? $parser->mStripState->unstripBoth( $parameters[ 'message' ]->getValue() ) : $parameters[ 'message' ]->getValue();

		if ( $message === '' ) {
			return '';
		}

		/**
		 * Non-escaping is safe bacause a user's message is passed through parser, which will
		 * handle unsafe HTM elements.
		 */
		$result = smwfEncodeMessages(
			[ $message ],
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

	/**
	 * @param ProcessingError[] $errors
	 * @return string
	 */
	private function getOutputForErrors( $errors ) {
		// TODO: see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1485
		return 'A fatal error occurred in the #info parser function';
	}

	public static function getHookDefinition() {
		return new HookDefinition(
			'info',
			[
				[
					'name' => 'message',
					'message' => 'smw-info-par-message',
				],
				[
					'name' => 'icon',
					'message' => 'smw-info-par-icon',
					'default' => 'info',
					'values' => [ 'info', 'warning', 'error', 'note' ],
				],
			],
			[
				'message',
				'icon'
			]
		);
	}

}
