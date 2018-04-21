<?php

namespace SMW\Page;

use Html;
use Title;
use WikiPage;
use SMW\ApplicationFactory;
use SMW\Message;
use SMW\Rule\RuleFactory;
use SMW\Rule\RuleDefinition;
use SMW\Rule\Exception\RuleTypeNotFoundException;
use SMWInfolink as Infolink;

/**
 * Special handling for relation/attribute description pages.
 * Some code based on CategoryPage.php
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RulePage extends Page {

	/**
	 * @var RuleFactory
	 */
	private $ruleFactory;

	/**
	 * @var RuleDefinition
	 */
	private $ruleDefinition;

	/**
	 * @see 3.0
	 *
	 * @param Title $title
	 * @param RuleFactory $ruleFactory
	 */
	public function __construct( Title $title, RuleFactory $ruleFactory ) {
		parent::__construct( $title );
		$this->ruleFactory = $ruleFactory;
	}

	/**
	 * @see 3.0
	 */
	public function setPage( WikiPage $page ) {
		$this->mPage = $page ;
	}

	/**
	 * @see Page::initParameters
	 */
	protected function initParameters() {
		return true;
	}

	/**
	 * @see Page::initHtml
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	protected function initHtml() {

		$text = '';
		$this->ruleDefinition = null;

		$content = $this->getPage()->getContent();
		$output = $this->getContext()->getOutput();

		if ( $content === null ) {
			return '';
		}

		try {
			$this->ruleDefinition = $this->ruleFactory->newRuleDefinition(
				$this->getPage()->getTitle()->getDBKey(),
				$content->getNativeData()
			);
		} catch ( RuleTypeNotFoundException $e ) {
			$text .= $this->isUnknown();
		}

		if ( $this->ruleDefinition !== null ) {
			$text .= $this->checkErrors();

			if ( $this->ruleDefinition->getSchema() === '' || $this->ruleDefinition->getSchema() === false ) {
				$text .= $this->noSchema( $this->ruleDefinition->get( RuleDefinition::RULE_TYPE ) );
			}

			$output->addHelpLink(
				Message::get(
					[
						'smw-rule-page-type-help-link',
						$this->ruleDefinition->get( RuleDefinition::RULE_TYPE )
					]
				),
				true
			);

			$text .= $this->getDescription();
		}

		return $text;
	}

	/**
	 * @see Page::beforeView
	 */
	protected function beforeView() {

		$content = $this->getPage()->getContent();

		if ( $content === null ) {
			return '';
		}

		return Html::rawElement(
			'h2',
			[],
			Message::get( 'smw-rule-page-definition' )
		) . Html::rawElement(
			'div',
			[
				'style' => 'margin-bottom:1em;'
			]
		);
	}

	/**
	 * @see Page::afterHtml
	 */
	protected function afterHtml() {

		if ( $this->ruleDefinition === null || $this->ruleDefinition->get( RuleDefinition::RULE_TAG, '' ) === '' ) {
			return '';
		}

		$tags = [];

		foreach ( $this->ruleDefinition->get( RuleDefinition::RULE_TAG ) as $class ) {
			$tags[] = Infolink::newPropertySearchLink( $class, 'Rule tag', $class, '' )->getHtml();
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'catlinks'
			],
			Message::get(
				'smw-rule-page-tag',
				Message::PARSE,
				Message::USER_LANGUAGE
			) . '&nbsp;' . implode( ' | ', $tags )
		);
	}

	/**
	 * @see Page::getHtml
	 */
	protected function getHtml() {
		return '';
	}

	private function isUnknown() {
		return Html::rawElement(
			'p',
			[
				'class' => 'smw-callout smw-callout-error'
			],
			'The rule type is unknown and therefore cannot be used.'
		);
	}

	private function isIncomplete() {
		return Html::rawElement(
			'p',
			[
				'class' => 'smw-callout smw-callout-warning'
			],
			'The rule definition has been marked incomplete and will not be applied to any subject.'
		);
	}

	private function noSchema( $type ) {
		return Html::rawElement(
			'p',
			[
				'class' => 'smw-callout smw-callout-warning'
			],
			"The $type type is missing a schema."
		);
	}

	private function checkErrors() {
		return $this->checkSchema();
	}

	private function checkSchema() {

		if ( !is_string( $this->ruleDefinition->getSchema() ) ) {
			return '';
		}

		$validator = $this->ruleFactory->newJsonSchemaValidator();

		$validator->validate(
			$this->ruleDefinition,
			$this->ruleDefinition->getSchema()
		);

		if ( $validator->isValid() ) {
			return '';
		}

		$errors = [];

		foreach ( $validator->getErrors() as $error ) {
			if ( isset( $error['property'] ) ) {
				$errors[] = $this->createErrorLink( $error['message'], null, $error['property'] );
			}
		}

		if ( $errors === [] ) {
			return '';
		}

		$html = Html::rawElement(
			'div',
			[
				'class' => ''
			],
			Html::rawElement(
				'ul',
				[],
				'<li>' . implode( '</li><li>', $errors ) . '</li>'
			)
		);

		$schema = substr(
			$this->ruleDefinition->getSchema(),
			strrpos( $this->ruleDefinition->getSchema(), '/' ) + 1
		);

		$html = Html::rawElement(
				'div',
				[
					'class' => ''
				],
				Html::rawElement(
					'span',
					[
						'class' => 'title float-left'
					],
					Message::get(
						[
							'smw-rule-error-schema',
							$schema
						],
						Message::TEXT,
						Message::USER_LANGUAGE
					)
				)
			) . Html::rawElement(
					'span',
					[
						'class' => ''
					]
			) . Html::rawElement(
				'div',
				[
					'class' => 'clear-both'
				]
		) . $html;

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-callout smw-callout-error'
			],
			$html
		);
	}

	private function getDescription() {

		$list = [];
		$description = $this->ruleDefinition->get( 'description', '' );
		$type = $this->ruleDefinition->get( 'type', '' );

		if ( $description !== '' ) {
			$list[] = Html::rawElement(
				'span',
				[
					'class' => 'plainlinks'
				],
				Message::get(
					'smw-rule-page-description',
					Message::PARSE,
					Message::USER_LANGUAGE
				) . '&nbsp;' . $description
			);
		}

		$list[] = Html::rawElement(
			'span',
			[
				'class' => 'plainlinks'
			],
			Message::get(
				'smw-rule-page-type',
				Message::PARSE,
				Message::USER_LANGUAGE
			) . '&nbsp;' . Infolink::newPropertySearchLink( $type, 'Rule type', $type, '' )->getHtml()
		);

		return Html::rawElement(
			'ul',
			[],
			'<li>' . implode( '</li><li>', $list ) . '</li>'
		);
	}

	private function createErrorLink( $msg, $title, $text ) {

		if ( strpos( $msg, 'smw-rule' ) !== false ) {
			$msg = Message::get(
				[
					$msg
				],
				Message::PARSE,
				Message::USER_LANGUAGE
			);
		}

		if ( $title instanceof Title ) {
			$html = Html::rawElement(
				'a',
				[
					'href' => $title->getFullURL()
				],
				$text
			);
		} else {
			$html = Html::rawElement(
				'span',
				[
					'class' => 'error'
				],
				$text
			);
		}

		return $html . '&nbsp;' . Html::rawElement(
			'span',
			[],
			"($msg)"
		);
	}

}
