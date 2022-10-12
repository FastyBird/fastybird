<?php declare(strict_types = 1);

/**
 * UserDeviceSpecifications.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          0.13.0
 *
 * @date           26.04.22
 */

namespace FastyBird\TuyaConnector\Entities\API;

use Nette;
use function array_map;

/**
 * OpenAPI device specifications entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserDeviceSpecifications implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param Array<UserDeviceSpecificationsFunction> $functions
	 * @param Array<UserDeviceSpecificationsStatus> $status
	 */
	public function __construct(
		private readonly string $category,
		private readonly array $functions,
		private readonly array $status,
	)
	{
	}

	public function getCategory(): string
	{
		return $this->category;
	}

	/**
	 * @return Array<UserDeviceSpecificationsFunction>
	 */
	public function getFunctions(): array
	{
		return $this->functions;
	}

	/**
	 * @return Array<UserDeviceSpecificationsStatus>
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
				static fn (UserDeviceSpecificationsStatus $item): array => $item->toArray(),
				$this->getStatus(),
			),
		];
	}

}
