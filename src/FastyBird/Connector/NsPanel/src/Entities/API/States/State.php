<?php declare(strict_types = 1);

/**
 * State.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\States;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;

/**
 * Device capability state base message data entity interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface State extends Entities\API\Entity
{

	public function getType(): Types\Capability;

	/**
	 * @return array<string, int|float|string|bool|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null>
	 */
	public function getProtocols(): array;

}
