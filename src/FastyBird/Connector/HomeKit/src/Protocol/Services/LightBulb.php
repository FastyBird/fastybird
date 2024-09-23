<?php declare(strict_types = 1);

/**
 * LightBulb.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           30.01.24
 */

namespace FastyBird\Connector\HomeKit\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Tools\Transformers as ToolsTransformers;
use FastyBird\Module\Devices\Types as DevicesTypes;
use function is_float;
use function is_int;

/**
 * HAP light bulb service
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LightBulb extends Generic
{

	public function recalculateValues(Protocol\Characteristics\Characteristic $characteristic): void
	{
		if (
			$characteristic->getName() === Types\CharacteristicType::COLOR_RED->value
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_GREEN->value
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_BLUE->value
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_WHITE->value
		) {
			$this->calculateRgbToHsb();

		} elseif (
			$characteristic->getName() === Types\CharacteristicType::HUE->value
			|| $characteristic->getName() === Types\CharacteristicType::SATURATION->value
			|| $characteristic->getName() === Types\CharacteristicType::BRIGHTNESS->value
		) {
			$this->calculateHsbToRgb();
		}
	}

	private function calculateRgbToHsb(): void
	{
		$redCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_RED);
		$greenCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_GREEN);
		$blueCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_BLUE);
		// Optional white channel
		$whiteCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_WHITE);

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

		$hue = $this->findCharacteristic(Types\CharacteristicType::HUE);
		$hue?->setExpectedValue($hsb->getHue());

		$saturation = $this->findCharacteristic(Types\CharacteristicType::SATURATION);
		$saturation?->setExpectedValue($hsb->getSaturation());

		$brightness = $this->findCharacteristic(Types\CharacteristicType::BRIGHTNESS);
		$brightness?->setExpectedValue($hsb->getBrightness());
	}

	private function calculateHsbToRgb(): void
	{
		$hueCharacteristic = $this->findCharacteristic(Types\CharacteristicType::HUE);
		$saturationCharacteristic = $this->findCharacteristic(Types\CharacteristicType::SATURATION);
		$brightnessCharacteristic = $this->findCharacteristic(Types\CharacteristicType::BRIGHTNESS);

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

		if ($this->hasCharacteristic(Types\CharacteristicType::COLOR_WHITE)) {
			$rgb = $rgb->toHsi()->toRgbw();
		}

		$red = $this->findCharacteristic(Types\CharacteristicType::COLOR_RED);
		$red?->setExpectedValue($rgb->getRed());

		$green = $this->findCharacteristic(Types\CharacteristicType::COLOR_GREEN);
		$green?->setExpectedValue($rgb->getGreen());

		$blue = $this->findCharacteristic(Types\CharacteristicType::COLOR_BLUE);
		$blue?->setExpectedValue($rgb->getBlue());

		$white = $this->findCharacteristic(Types\CharacteristicType::COLOR_WHITE);
		$white?->setExpectedValue($rgb->getWhite());
	}

}
