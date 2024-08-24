<?php declare(strict_types = 1);

/**
 * Lightbulb.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Library\Tools\Transformers as ToolsTransformers;
use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Shelly light bulb service
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Lightbulb extends HomeKitProtocol\Services\Generic
{

	public function recalculateValues(
		HomeKitProtocol\Characteristics\Characteristic $characteristic,
		bool $fromDevice,
	): void
	{
		$updatePropertyType = $fromDevice ? DevicesTypes\PropertyType::DYNAMIC : DevicesTypes\PropertyType::MAPPED;

		if (
			$characteristic->getName() === HomeKitTypes\CharacteristicType::COLOR_RED->value
			|| $characteristic->getName() === HomeKitTypes\CharacteristicType::COLOR_GREEN->value
			|| $characteristic->getName() === HomeKitTypes\CharacteristicType::COLOR_BLUE->value
			|| $characteristic->getName() === HomeKitTypes\CharacteristicType::COLOR_WHITE->value
		) {
			$this->calculateRgbToHsb($updatePropertyType);

		} elseif (
			$characteristic->getName() === HomeKitTypes\CharacteristicType::HUE->value
			|| $characteristic->getName() === HomeKitTypes\CharacteristicType::SATURATION->value
			|| $characteristic->getName() === HomeKitTypes\CharacteristicType::BRIGHTNESS->value
		) {
			$this->calculateHsbToRgb($updatePropertyType);
		}
	}

	private function calculateRgbToHsb(
		DevicesTypes\PropertyType $updatePropertyType,
	): void
	{
		$redCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_RED);
		$greenCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_GREEN);
		$blueCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_BLUE);
		// Optional white channel
		$whiteCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_WHITE);

		if (
			is_int($redCharacteristic?->getValue())
			&& is_int($greenCharacteristic?->getValue())
			&& is_int($blueCharacteristic?->getValue())
		) {
			$rgb = new ToolsTransformers\RgbTransformer(
				$redCharacteristic->getValue(),
				$greenCharacteristic->getValue(),
				$blueCharacteristic->getValue(),
				is_int($whiteCharacteristic?->getValue()) ? $whiteCharacteristic->getValue() : null,
			);

			$hsb = $rgb->toHsb();

		} else {
			$hsb = new ToolsTransformers\HsbTransformer(0, 0, 0);
		}

		$hue = $this->findCharacteristic(HomeKitTypes\CharacteristicType::HUE);

		if (
			$hue !== null
			&& (
				$hue->getProperty() === null
				|| $hue->getProperty()::getType() === $updatePropertyType->value
			)
		) {
			$hue->setValue($hsb->getHue());
		}

		$saturation = $this->findCharacteristic(HomeKitTypes\CharacteristicType::SATURATION);

		if (
			$saturation !== null
			&& (
				$saturation->getProperty() === null
				|| $saturation->getProperty()::getType() === $updatePropertyType->value
			)
		) {
			$saturation->setValue($hsb->getSaturation());
		}

		$brightness = $this->findCharacteristic(HomeKitTypes\CharacteristicType::BRIGHTNESS);

		if (
			$brightness !== null
			&& (
				$brightness->getProperty() === null
				|| $brightness->getProperty()::getType() === $updatePropertyType->value
			)
		) {
			$brightness->setValue($hsb->getBrightness());
		}
	}

	private function calculateHsbToRgb(
		DevicesTypes\PropertyType $updatePropertyType,
	): void
	{
		$hueCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::HUE);
		$saturationCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::SATURATION);
		$brightnessCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::BRIGHTNESS);

		if (
			(
				is_int($hueCharacteristic?->getValue())
				|| is_float($hueCharacteristic?->getValue())
			)
			&& (
				is_int($saturationCharacteristic?->getValue())
				|| is_float($saturationCharacteristic?->getValue())
			)
			&& is_int($brightnessCharacteristic?->getValue())
		) {
			$brightness = $brightnessCharacteristic->getValue();

			// If brightness is controlled with separate property, we will use 100% brightness for calculation
			if (
				$brightnessCharacteristic->getProperty() !== null
				&& $brightnessCharacteristic->getProperty()::getType() === DevicesTypes\PropertyType::MAPPED->value
			) {
				$brightness = 100;
			}

			$hsb = new ToolsTransformers\HsbTransformer(
				$hueCharacteristic->getValue(),
				$saturationCharacteristic->getValue(),
				$brightness,
			);

			$rgb = $hsb->toRgb();

		} else {
			$rgb = new ToolsTransformers\RgbTransformer(0, 0, 0);
		}

		if ($this->hasCharacteristic(HomeKitTypes\CharacteristicType::COLOR_WHITE)) {
			$rgb = $rgb->toHsi()->toRgbw();
		}

		$red = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_RED);

		if (
			$red !== null
			&& (
				$red->getProperty() === null
				|| $red->getProperty()::getType() === $updatePropertyType->value
			)
		) {
			$red->setValue($rgb->getRed());
		}

		$green = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_GREEN);

		if (
			$green !== null
			&& (
				$green->getProperty() === null
				|| $green->getProperty()::getType() === $updatePropertyType->value
			)
		) {
			$green->setValue($rgb->getGreen());
		}

		$blue = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_BLUE);

		if (
			$blue !== null
			&& (
				$blue->getProperty() === null
				|| $blue->getProperty()::getType() === $updatePropertyType->value
			)
		) {
			$blue->setValue($rgb->getBlue());
		}

		$white = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_WHITE);

		if (
			$white !== null
			&& (
				$white->getProperty() === null
				|| $white->getProperty()::getType() === $updatePropertyType->value
			)
			&& $rgb->getWhite() !== null
		) {
			$white->setValue($rgb->getWhite());
		}
	}

}
