<?php

namespace SMW\Property\DeclarationExaminer;

use SMW\DIProperty;
use SMW\Property\DeclarationExaminer as IDeclarationExaminer;
use SMW\Protection\ProtectionValidator;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ProtectionExaminer extends DeclarationExaminer {

	/**
	 * @var ProtectionValidator
	 */
	private $protectionValidator;

	/**
	 * @since 3.1
	 *
	 * @param DeclarationExaminer $declarationExaminer
	 * @param ProtectionValidator $protectionValidator
	 */
	public function __construct( IDeclarationExaminer $declarationExaminer, ProtectionValidator $protectionValidator ) {
		$this->declarationExaminer = $declarationExaminer;
		$this->protectionValidator = $protectionValidator;
	}

	/**
	 * @see ExaminerDecorator::validate
	 *
	 * {@inheritDoc}
	 */
	protected function validate( DIProperty $property ) {

		if ( $this->declarationExaminer->isLocked() ) {
			return;
		}

		$subject = $property->getCanonicalDiWikiPage();
		$title = $subject->getTitle();

		$this->checkCreateProtectionRight( $title, $property );
		$this->checkEditProtectionRight( $title, $property );
	}

	private function checkCreateProtectionRight( $title, $property ) {

		if ( !$this->protectionValidator->hasCreateProtection( $title ) ) {
			return;
		}

		$createProtectionRight = $this->protectionValidator->getCreateProtectionRight();
		$msg = 'smw-create-protection';

		if ( $title->exists() ) {
			$msg = 'smw-create-protection-exists';
		}

		$this->messages[] = [ 'warning', $msg, $property->getLabel(), $createProtectionRight ];
	}

	private function checkEditProtectionRight( $title, $property ) {

		$editProtectionRight = $this->protectionValidator->getEditProtectionRight();

		if ( $this->protectionValidator->hasEditProtection( $title ) ) {
			$severity = 'warning';

			if ( $property->isUserDefined() ) {
				$severity = 'error';
			}

			$this->messages[] = [ $severity, 'smw-edit-protection', $editProtectionRight ];
		}

		// Examines whether the setting `smwgEditProtectionRight` contains an
		// appropriate value or is disabled in order for the `Is edit protected`
		// property
		if ( $property->getKey() !== '_EDIP' ) {
			return;
		}

		if ( $editProtectionRight !== false ) {
			return;
		}

		$this->messages[] = [ 'warning', 'smw-edit-protection-disabled', $property->getCanonicalLabel() ];
	}

}
