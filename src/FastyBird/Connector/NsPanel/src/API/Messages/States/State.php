<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Types;

/**
 * Device capability state base message interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface State extends API\Messages\Message
{

	public function getType(): Types\Capability;

	/**
	 * @return array<string, int|float|string|bool|Types\Payloads\Payload|null>
	 */
	public function getState(): array;

}
