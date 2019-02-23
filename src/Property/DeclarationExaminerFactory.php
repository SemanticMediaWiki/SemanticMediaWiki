<?php

namespace SMW\Property;

use SMW\Property\DeclarationExaminer\CommonExaminer;
use SMW\Property\DeclarationExaminer\ChangePropagationExaminer;
use SMW\Property\DeclarationExaminer\ProtectionExaminer;
use SMW\Property\DeclarationExaminer\PredefinedPropertyExaminer;
use SMW\Property\DeclarationExaminer\UserdefinedPropertyExaminer;
use SMW\Store;
use SMW\SemanticData;
use SMW\ApplicationFactory;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DeclarationExaminerFactory {

	/**
	 * @since 3.1
	 *
	 * @return DeclarationExaminerMsgBuilder
	 */
	public function newDeclarationExaminerMsgBuilder() {
		return new DeclarationExaminerMsgBuilder();
	}

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param SemanticData|null $semanticData
	 *
	 * @return DeclarationExaminer
	 */
	public function newDeclarationExaminer( Store $store, SemanticData $semanticData = null ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$commonExaminer = new CommonExaminer(
			$store,
			$semanticData
		);

		$commonExaminer->setPropertyReservedNameList(
			$settings->get( 'smwgPropertyReservedNameList' )
		);

		$changePropagationExaminer = new ChangePropagationExaminer(
			$commonExaminer,
			$store,
			$semanticData
		);

		$changePropagationExaminer->setChangePropagationProtection(
			$settings->get( 'smwgChangePropagationProtection' )
		);

		$protectionExaminer = new ProtectionExaminer(
			$changePropagationExaminer,
			$applicationFactory->singleton( 'ProtectionValidator' )
		);

		$predefinedPropertyExaminer = new PredefinedPropertyExaminer(
			$protectionExaminer
		);

		$userdefinedPropertyExaminer = new UserdefinedPropertyExaminer(
			$predefinedPropertyExaminer,
			$store
		);

		return $userdefinedPropertyExaminer;
	}

}
