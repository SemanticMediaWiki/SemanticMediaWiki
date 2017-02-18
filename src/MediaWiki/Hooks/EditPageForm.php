<?php

namespace SMW\MediaWiki\Hooks;

use SMW\NamespaceExaminer;
use SMW\DIProperty;
use SMW\Message;
use EditPage;
use Html;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EditPageForm extends HookHandler {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var boolean
	 */
	private $enabledEditPageHelp = false;

	/**
	 * @since 2.5
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $enabledEditPageHelp
	 */
	public function isEnabledEditPageHelp( $enabledEditPageHelp ) {
		$this->enabledEditPageHelp = $enabledEditPageHelp;
	}

	/**
	 * @since 2.1
	 *
	 * @param EditPage $editPage
	 *
	 * @return boolean
	 */
	public function process( EditPage $editPage ) {

		if ( !$this->enabledEditPageHelp ) {
			return true;
		}

		$this->addHelpFormTo( $editPage );

		return true;
	}

	private function addHelpFormTo( $editPage ) {

		$msgKey = $this->getMessageKey(
			$editPage->getTitle()
		);

		$message = Message::get(
			$msgKey,
			Message::PARSE,
			Message::USER_LANGUAGE
		);

		$html =	Html::rawElement(
			'div',
			array(
				'class' => 'smw-editpage-help'
			),
			Html::rawElement(
				'p',
				array(
					'data-msgKey' => $msgKey
				),
				$message
			)
		);

		$editPage->editFormPageTop .= $html;
	}

	private function getMessageKey( $title ) {

		$text = $title->getText();
		$namespace = $title->getNamespace();

		if ( $namespace === SMW_NS_PROPERTY ) {
			if ( DIProperty::newFromUserLabel( $text )->isUserDefined() ) {
				return 'smw-editpage-property-annotation-enabled';
			} else {
				return 'smw-editpage-property-annotation-disabled';
			}
		} elseif ( $namespace === SMW_NS_CONCEPT ) {
			return 'smw-editpage-concept-annotation-enabled';
		} elseif ( $this->namespaceExaminer->isSemanticEnabled( $namespace ) ) {
			return 'smw-editpage-annotation-enabled';
		}

		return 'smw-editpage-annotation-disabled';
	}

}
