<?php declare(strict_types = 1);

/**
 * Toggles.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Statuses;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;
use function array_map;

/**
 * Toggle control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NamedStatus implements Status
{

	public function __construct(
		private readonly string|null $name,
		private readonly Status $status,
	)
	{
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getStatus(): Status
	{
		return $this->status;
	}

	public function getType(): Types\Capability
	{
		return $this->status->getType();
	}

	/**
	 * @return int|float|string|bool|array<int>|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getValue(): int|float|string|bool|array|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null
	{
		return $this->status->getValue();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(['name' => $this->getName()], $this->status->toArray());
	}

	public function toJson(): object
	{
		return $this->status->toJson();
	}

}
