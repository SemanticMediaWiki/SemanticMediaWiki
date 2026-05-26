<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\EditPage__showEditForm_initialHook;
use MediaWiki\Html\Html;
use MediaWiki\User\Options\UserOptionsLookup;
use SMW\DataItems\Property;
use SMW\GroupPermissions;
use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\PermissionManager;
use SMW\NamespaceExaminer;
use SMW\Settings;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class EditPageForm implements EditPage__showEditForm_initialHook {

	use MessageLocalizerTrait;

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly UserOptionsLookup $userOptionsLookup,
		private readonly Settings $settings,
		private readonly PermissionManager $permissionManager,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
	public function onEditPage__showEditForm_initial( $editor, $out ) {
		$html = '';
		$user = $out->getUser();
		$permissionExaminer = new PermissionExaminer( $this->permissionManager, $user );

		if (
			$this->settings->get( 'smwgEnabledEditPageHelp' ) &&
			$permissionExaminer->hasPermissionOf( GroupPermissions::VIEW_EDITPAGE_INFO ) &&
			!$this->userOptionsLookup->getOption( $user, GetPreferences::DISABLE_EDITPAGE_INFO, false ) ) {
			$html = $this->buildHTML( $editor->getTitle() );
		}

		$editor->editFormPageTop .= $html;

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
