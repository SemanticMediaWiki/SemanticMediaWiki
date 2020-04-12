<?php

namespace SMW\MediaWiki\Hooks;

use EditPage;
use Html;
use SMW\DIProperty;
use SMW\Message;
use SMW\NamespaceExaminer;
use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\GroupPermissions;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EditPageForm implements HookListener {

	use MessageLocalizerTrait;
	use OptionsAwareTrait;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var PermissionExaminer
	 */
	private $permissionExaminer;

	/**
	 * @var PreferenceExaminer
	 */
	private $preferenceExaminer;

	/**
	 * @since 2.5
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 * @param PermissionExaminer $permissionExaminer
	 * @param PreferenceExaminer $preferenceExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer, PermissionExaminer $permissionExaminer, PreferenceExaminer $preferenceExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
		$this->permissionExaminer = $permissionExaminer;
		$this->preferenceExaminer = $preferenceExaminer;
	}

	/**
	 * @since 2.1
	 *
	 * @param EditPage $editPage
	 *
	 * @return boolean
	 */
	public function process( EditPage $editPage ) {

		$html = '';

		if (
			$this->getOption( 'smwgEnabledEditPageHelp', false ) &&
			$this->permissionExaminer->hasPermissionOf( GroupPermissions::VIEW_EDITPAGE_INFO ) &&
			!$this->preferenceExaminer->hasPreferenceOf( GetPreferences::DISABLE_EDITPAGE_INFO ) ) {
			$html = $this->buildHTML( $editPage->getTitle() );
		}

		$editPage->editFormPageTop .= $html;

		return true;
	}

	private function buildHTML( $title ) {

		$msgKey = $this->getMessageKey(
			$title
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-editpage-help'
			],
			Html::rawElement(
				'p',
				[
					'data-msgKey' => $msgKey
				],
				$this->msg( $msgKey, Message::PARSE )
			)
		);
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
