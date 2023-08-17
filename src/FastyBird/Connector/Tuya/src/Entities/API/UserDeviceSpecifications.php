<?php declare(strict_types = 1);

/**
 * UserDeviceSpecifications.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Orisai\ObjectMapper;
use function array_map;

/**
 * OpenAPI user device specifications entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserDeviceSpecifications implements Entity
{

	/**
	 * @param array<UserDeviceSpecificationsFunction> $functions
	 * @param array<UserDeviceSpecificationsState> $status
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $category,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceSpecificationsFunction::class),
		)]
		private readonly array $functions,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceSpecificationsState::class),
		)]
		private readonly array $status,
	)
	{
	}

	public function getCategory(): string
	{
		return $this->category;
	}

	/**
	 * @return array<UserDeviceSpecificationsFunction>
	 */
	public function getFunctions(): array
	{
		return $this->functions;
	}

	/**
	 * @return array<UserDeviceSpecificationsState>
	 */
	public function getStatus(): array
	{
		return $this->status;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'category' => $this->getCategory(),
			'functions' => array_map(
				static fn (UserDeviceSpecificationsFunction $item): array => $item->toArray(),
				$this->getFunctions(),
			),
			'status' => array_map(
				static fn (UserDeviceSpecificationsState $item): array => $item->toArray(),
				$this->getStatus(),
			),
		];
	}

}
