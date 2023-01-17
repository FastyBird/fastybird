<?php declare(strict_types = 1);

/**
 * OpenPulsarFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           16.01.23
 */

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Types;

/**
 * OpenPulsar API factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface OpenPulsarFactory
{

	public function create(
		string $identifier,
		string $accessId,
		string $accessSecret,
		Types\OpenPulsarTopic $topic,
		Types\OpenPulsarEndpoint $endpoint,
	): OpenPulsar;

}
