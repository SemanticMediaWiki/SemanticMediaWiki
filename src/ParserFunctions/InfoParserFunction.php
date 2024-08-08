<?php

namespace SMW\ParserFunctions;

use ParamProcessor\ProcessingError;
use ParamProcessor\ProcessingResult;
use Parser;
use ParserHooks\HookDefinition;
use ParserHooks\HookHandler;
use SMWOutputs;
use SMW\Highlighter;

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

		/**
		 * Non-escaping is safe bacause a user's message is passed through parser, which will
		 * handle unsafe HTM elements.
		 */
		$message = $parameters['message']->getValue();

		if ( $parser->getStripState() ) {
			$message = $parser->getStripState()->unstripBoth( $message );
		}

		// If the message contains another highlighter (caused by recursive
		// parsing etc.) remove the tags to allow to show the text without making
		// the JS go berserk due to having more than one `smw-highlighter`
		if ( strpos( $message ?? '', 'smw-highlighter' ) !== '' ) {
			$message = preg_replace_callback(
					"/" . "<span class=\"smw-highlighter\"(.*)?>(.*)?<\/span>" . "/m",
					function ( $matches ) {
						return strip_tags( $matches[0] );
					},
					$message ?? ''
			);
		}

		if ( $message === '' ) {
			return '';
		}

		$highlighter = Highlighter::factory(
			$parameters['icon']->getValue()
		);

		$highlighter->setContent( [
			'caption'    => null,
			'content'    => Highlighter::decode( $message ),
			'maxwidth'   => $parameters['max-width']->getValue(),
			'themeclass' => $parameters['theme']->getValue()
		] );

		$result = $highlighter->getHtml();

		if ( !is_null( $parser->getTitle() ) && $parser->getTitle()->isSpecialPage() ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		} else {
			SMWOutputs::commitToParser( $parser );
		}

		return $result;
	}

	/**
	 * @param ProcessingError[] $errors
	 *
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
				[
					'name' => 'max-width',
					'default' => '',
					'message' => 'smw-info-par-max-width',
				],
				[
					'name' => 'theme',
					'default' => '',
					'values' => [ 'square-border', 'square-border-light' ],
					'message' => 'smw-info-par-theme',
				]
			],
			[
				'message',
				'icon'
			]
		);
	}

}
