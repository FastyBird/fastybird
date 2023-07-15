<?php declare(strict_types = 1);

/**
 * DataPointStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\Messages;

use FastyBird\Connector\NsPanel\Types;
use Nette;
use function is_scalar;
use function strval;

/**
 * Device capability status entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CapabilityStatus implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param int|float|string|bool|array<int>|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null $value
	 */
	public function __construct(
		private readonly Types\Capability $capability,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		private readonly int|float|string|bool|array|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null $value,
		private readonly string|null $name,
	)
	{
	}

	public function getCapability(): Types\Capability
	{
		return $this->capability;
	}

	/**
	 * @return int|float|string|bool|array<int>|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getValue(): int|float|string|bool|array|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null
	{
		return $this->value;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'capability' => $this->getCapability()->getValue(),
			'value' => is_scalar($this->getValue()) ? $this->getValue() : strval($this->getValue()),
			'name' => $this->getName(),
		];
	}

}
