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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Transformers as ToolsTransformers;
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

	public function recalculateActualValues(
		Protocol\Characteristics\Characteristic $characteristic,
	): void
	{
		parent::recalculateActualValues($characteristic);

		$updatePropertyType = MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED);

		if (
			$characteristic->getName() === Types\CharacteristicType::COLOR_RED
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_GREEN
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_BLUE
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_WHITE
		) {
			$this->calculateActualValuesRgbToHsb($updatePropertyType);

		} elseif (
			$characteristic->getName() === Types\CharacteristicType::HUE
			|| $characteristic->getName() === Types\CharacteristicType::SATURATION
			|| $characteristic->getName() === Types\CharacteristicType::BRIGHTNESS
		) {
			$this->calculateActualValuesHsbToRgb($updatePropertyType);
		}
	}

	public function recalculateExpectedValues(
		Protocol\Characteristics\Characteristic $characteristic,
	): void
	{
		parent::recalculateExpectedValues($characteristic);

		$updatePropertyType = MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC);

		if (
			$characteristic->getName() === Types\CharacteristicType::COLOR_RED
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_GREEN
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_BLUE
			|| $characteristic->getName() === Types\CharacteristicType::COLOR_WHITE
		) {
			$this->calculateExpectedValuesRgbToHsb($updatePropertyType);

		} elseif (
			$characteristic->getName() === Types\CharacteristicType::HUE
			|| $characteristic->getName() === Types\CharacteristicType::SATURATION
			|| $characteristic->getName() === Types\CharacteristicType::BRIGHTNESS
		) {
			$this->calculateExpectedValuesHsbToRgb($updatePropertyType);
		}
	}

	private function calculateActualValuesRgbToHsb(
		MetadataTypes\PropertyType $updatePropertyType,
	): void
	{
		$redCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_RED);
		$greenCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_GREEN);
		$blueCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_BLUE);
		// Optional white channel
		$whiteCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_WHITE);

		if (
			is_int($redCharacteristic?->getActualValue())
			&& is_int($greenCharacteristic?->getActualValue())
			&& is_int($blueCharacteristic?->getActualValue())
		) {
			$rgb = new ToolsTransformers\RgbTransformer(
				$redCharacteristic->getActualValue(),
				$greenCharacteristic->getActualValue(),
				$blueCharacteristic->getActualValue(),
				is_int($whiteCharacteristic?->getActualValue()) ? $whiteCharacteristic->getActualValue() : null,
			);

			$hsb = $rgb->toHsb();

		} else {
			$hsb = new ToolsTransformers\HsbTransformer(0, 0, 0);
		}

		$hue = $this->findCharacteristic(Types\CharacteristicType::HUE);

		if (
			$hue !== null
			&& (
				$hue->getProperty() === null
				|| $hue->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$hue->setActualValue($hsb->getHue());
		}

		$saturation = $this->findCharacteristic(Types\CharacteristicType::SATURATION);

		if (
			$saturation !== null
			&& (
				$saturation->getProperty() === null
				|| $saturation->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$saturation->setActualValue($hsb->getSaturation());
		}

		$brightness = $this->findCharacteristic(Types\CharacteristicType::BRIGHTNESS);

		if (
			$brightness !== null
			&& (
				$brightness->getProperty() === null
				|| $brightness->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$brightness->setActualValue($hsb->getBrightness());
		}
	}

	private function calculateActualValuesHsbToRgb(
		MetadataTypes\PropertyType $updatePropertyType,
	): void
	{
		$hueCharacteristic = $this->findCharacteristic(Types\CharacteristicType::HUE);
		$saturationCharacteristic = $this->findCharacteristic(Types\CharacteristicType::SATURATION);
		$brightnessCharacteristic = $this->findCharacteristic(Types\CharacteristicType::BRIGHTNESS);

		if (
			(
				is_int($hueCharacteristic?->getActualValue())
				|| is_float($hueCharacteristic?->getActualValue())
			)
			&& (
				is_int($saturationCharacteristic?->getActualValue())
				|| is_float($saturationCharacteristic?->getActualValue())
			)
			&& is_int($brightnessCharacteristic?->getActualValue())
		) {
			$brightness = $brightnessCharacteristic->getActualValue();

			// If brightness is controlled with separate property, we will use 100% brightness for calculation
			if (
				$brightnessCharacteristic->getProperty() !== null
				&& $brightnessCharacteristic->getProperty()->getType() === MetadataTypes\PropertyType::MAPPED
			) {
				$brightness = 100;
			}

			$hsb = new ToolsTransformers\HsbTransformer(
				$hueCharacteristic->getActualValue(),
				$saturationCharacteristic->getActualValue(),
				$brightness,
			);

			$rgb = $hsb->toRgb();

		} else {
			$rgb = new ToolsTransformers\RgbTransformer(255, 255, 255);
		}

		if ($this->hasCharacteristic(Types\CharacteristicType::COLOR_WHITE)) {
			$rgb = $rgb->toHsi()->toRgbw();
		}

		$red = $this->findCharacteristic(Types\CharacteristicType::COLOR_RED);

		if (
			$red !== null
			&& (
				$red->getProperty() === null
				|| $red->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$red->setActualValue($rgb->getRed());
		}

		$green = $this->findCharacteristic(Types\CharacteristicType::COLOR_GREEN);

		if (
			$green !== null
			&& (
				$green->getProperty() === null
				|| $green->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$green->setActualValue($rgb->getGreen());
		}

		$blue = $this->findCharacteristic(Types\CharacteristicType::COLOR_BLUE);

		if (
			$blue !== null
			&& (
				$blue->getProperty() === null
				|| $blue->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$blue->setActualValue($rgb->getBlue());
		}

		$white = $this->findCharacteristic(Types\CharacteristicType::COLOR_WHITE);

		if (
			$white !== null
			&& (
				$white->getProperty() === null
				|| $white->getProperty()->getType() === $updatePropertyType->getValue()
			)
			&& $rgb->getWhite() !== null
		) {
			$white->setActualValue($rgb->getWhite());
		}
	}

	private function calculateExpectedValuesRgbToHsb(
		MetadataTypes\PropertyType $updatePropertyType,
	): void
	{
		$redCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_RED);
		$greenCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_GREEN);
		$blueCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_BLUE);
		// Optional white channel
		$whiteCharacteristic = $this->findCharacteristic(Types\CharacteristicType::COLOR_WHITE);

		if (
			is_int($redCharacteristic?->getExpectedValue())
			&& is_int($greenCharacteristic?->getExpectedValue())
			&& is_int($blueCharacteristic?->getExpectedValue())
		) {
			$rgb = new ToolsTransformers\RgbTransformer(
				$redCharacteristic->getExpectedValue(),
				$greenCharacteristic->getExpectedValue(),
				$blueCharacteristic->getExpectedValue(),
				is_int($whiteCharacteristic?->getExpectedValue()) ? $whiteCharacteristic->getExpectedValue() : null,
			);

			$hsb = $rgb->toHsb();

		} else {
			$hsb = new ToolsTransformers\HsbTransformer(0, 0, 0);
		}

		$hue = $this->findCharacteristic(Types\CharacteristicType::HUE);

		if (
			$hue !== null
			&& (
				$hue->getProperty() === null
				|| $hue->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$hue->setExpectedValue($hsb->getHue());
		}

		$saturation = $this->findCharacteristic(Types\CharacteristicType::SATURATION);

		if (
			$saturation !== null
			&& (
				$saturation->getProperty() === null
				|| $saturation->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$saturation->setExpectedValue($hsb->getSaturation());
		}

		$brightness = $this->findCharacteristic(Types\CharacteristicType::BRIGHTNESS);

		if (
			$brightness !== null
			&& (
				$brightness->getProperty() === null
				|| $brightness->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$brightness->setExpectedValue($hsb->getBrightness());
		}
	}

	private function calculateExpectedValuesHsbToRgb(
		MetadataTypes\PropertyType $updatePropertyType,
	): void
	{
		$hueCharacteristic = $this->findCharacteristic(Types\CharacteristicType::HUE);
		$saturationCharacteristic = $this->findCharacteristic(Types\CharacteristicType::SATURATION);
		$brightnessCharacteristic = $this->findCharacteristic(Types\CharacteristicType::BRIGHTNESS);

		if (
			(
				is_int($hueCharacteristic?->getExpectedValue())
				|| is_float($hueCharacteristic?->getExpectedValue())
			)
			&& (
				is_int($saturationCharacteristic?->getExpectedValue())
				|| is_float($saturationCharacteristic?->getExpectedValue())
			)
			&& is_int($brightnessCharacteristic?->getExpectedValue())
		) {
			$brightness = $brightnessCharacteristic->getExpectedValue();

			// If brightness is controlled with separate property, we will use 100% brightness for calculation
			if (
				$brightnessCharacteristic->getProperty() !== null
				&& $brightnessCharacteristic->getProperty()->getType() === MetadataTypes\PropertyType::MAPPED
			) {
				$brightness = 100;
			}

			$hsb = new ToolsTransformers\HsbTransformer(
				$hueCharacteristic->getExpectedValue(),
				$saturationCharacteristic->getExpectedValue(),
				$brightness,
			);

			$rgb = $hsb->toRgb();

		} else {
			$rgb = new ToolsTransformers\RgbTransformer(255, 255, 255);
		}

		if ($this->hasCharacteristic(Types\CharacteristicType::COLOR_WHITE)) {
			$rgb = $rgb->toHsi()->toRgbw();
		}

		$red = $this->findCharacteristic(Types\CharacteristicType::COLOR_RED);

		if (
			$red !== null
			&& (
				$red->getProperty() === null
				|| $red->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$red->setExpectedValue($rgb->getRed());
		}

		$green = $this->findCharacteristic(Types\CharacteristicType::COLOR_GREEN);

		if (
			$green !== null
			&& (
				$green->getProperty() === null
				|| $green->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$green->setExpectedValue($rgb->getGreen());
		}

		$blue = $this->findCharacteristic(Types\CharacteristicType::COLOR_BLUE);

		if (
			$blue !== null
			&& (
				$blue->getProperty() === null
				|| $blue->getProperty()->getType() === $updatePropertyType->getValue()
			)
		) {
			$blue->setExpectedValue($rgb->getBlue());
		}

		$white = $this->findCharacteristic(Types\CharacteristicType::COLOR_WHITE);

		if (
			$white !== null
			&& (
				$white->getProperty() === null
				|| $white->getProperty()->getType() === $updatePropertyType->getValue()
			)
			&& $rgb->getWhite() !== null
		) {
			$white->setExpectedValue($rgb->getWhite());
		}
	}

}
