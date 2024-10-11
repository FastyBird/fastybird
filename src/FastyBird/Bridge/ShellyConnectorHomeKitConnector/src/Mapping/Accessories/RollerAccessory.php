<?php declare(strict_types = 1);

/**
 * RollerAccessory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 * @since          1.0.0
 *
 * @date           22.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping\Accessories;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Types;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Orisai\ObjectMapper;
use TypeError;
use ValueError;

/**
 * Rollers accessories mapping configuration
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class RollerAccessory extends Accessory
{

	/**
	 * @param array<string> $models
	 * @param array<HomeKitTypes\AccessoryCategory> $categories
	 * @param array<Mapping\Services\Service> $services
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\DeviceType::ROLLER->value])]
		private string $type,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $models,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\BackedEnumValue(class: HomeKitTypes\AccessoryCategory::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $categories,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Services\WindowCoveringService::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $services,
	)
	{
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getType(): Types\DeviceType
	{
		return Types\DeviceType::from($this->type);
	}

	/**
	 * @return array<string>
	 */
	public function getModels(): array
	{
		return $this->models;
	}

	/**
	 * @return array<HomeKitTypes\AccessoryCategory>
	 */
	public function getCategories(): array
	{
		return $this->categories;
	}

	/**
	 * @return array<Mapping\Services\Service>
	 */
	public function getServices(): array
	{
		return $this->services;
	}

}
