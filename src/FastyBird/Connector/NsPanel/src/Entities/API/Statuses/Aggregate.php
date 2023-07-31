<?php declare(strict_types = 1);

/**
 * Aggregate.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           31.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Statuses;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;

/**
 * Device capability aggregated status base message data entity interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Aggregate extends Entities\API\Entity
{

	public function getType(): Types\Capability;

	/**
	 * @return array<int|float|string|bool|array<int>|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload>
	 */
	public function getValue(): array;

	/**
	 * @return array<Status>
	 */
	public function getAggregates(): array;

}
