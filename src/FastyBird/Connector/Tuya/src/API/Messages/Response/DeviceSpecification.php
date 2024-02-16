<?php declare(strict_types = 1);

/**
 * DeviceSpecification.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           29.04.22
 */

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * OpenAPI device specification message
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DeviceSpecification implements API\Messages\Message
{

	/**
	 * @param array<DeviceSpecificationFunction> $functions
	 * @param array<DeviceSpecificationState> $status
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $category,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSpecificationFunction::class),
		)]
		private array $functions,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSpecificationState::class),
		)]
		private array $status,
	)
	{
	}

	public function getCategory(): string
	{
		return $this->category;
	}

	/**
	 * @return array<DeviceSpecificationFunction>
	 */
	public function getFunctions(): array
	{
		return $this->functions;
	}

	/**
	 * @return array<DeviceSpecificationState>
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
				static fn (DeviceSpecificationFunction $item): array => $item->toArray(),
				$this->getFunctions(),
			),
			'status' => array_map(
				static fn (DeviceSpecificationState $item): array => $item->toArray(),
				$this->getStatus(),
			),
		];
	}

}
