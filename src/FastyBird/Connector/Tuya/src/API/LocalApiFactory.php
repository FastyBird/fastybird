<?php declare(strict_types = 1);

/**
 * LocalApiFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           05.09.22
 */

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Types;

/**
 * Local device API factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface LocalApiFactory
{

	/**
	 * @param array<Entities\Clients\LocalChild> $children
	 */
	public function create(
		string $identifier,
		string|null $gateway,
		string|null $nodeId,
		string $localKey,
		string $ipAddress,
		Types\DeviceProtocolVersion $protocolVersion,
		array $children = [],
	): LocalApi;

}
