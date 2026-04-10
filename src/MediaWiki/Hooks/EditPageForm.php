<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\EditPage\EditPage;
use MediaWiki\Html\Html;
use SMW\DataItems\Property;
use SMW\GroupPermissions;
use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\HookListener;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\NamespaceExaminer;
use SMW\OptionsAwareTrait;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class EditPageForm implements HookListener {

	use MessageLocalizerTrait;
	use OptionsAwareTrait;

	/**
	 * @since 2.5
	 */
	public function __construct(
		private NamespaceExaminer $namespaceExaminer,
		private PermissionExaminer $permissionExaminer,
		private PreferenceExaminer $preferenceExaminer,
	) {
	}

	/**
	 * @since 2.1
	 *
	 * @param EditPage $editPage
	 *
	 * @return bool
	 */
	public function process( EditPage $editPage ): bool {
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

	private function getMessageKey( $title ): string {
		$text = $title->getText();
		$namespace = $title->getNamespace();

		if ( $namespace === SMW_NS_PROPERTY ) {
			if ( Property::newFromUserLabel( $text )->isUserDefined() ) {
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
