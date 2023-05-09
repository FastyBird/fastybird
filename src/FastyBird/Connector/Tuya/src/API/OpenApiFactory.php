<?php declare(strict_types = 1);

/**
 * OpenApiFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Types;

/**
 * OpenAPI API factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface OpenApiFactory
{

	public function create(
		string $identifier,
		string $accessId,
		string $accessSecret,
		Types\OpenApiEndpoint $endpoint,
		string $lang = 'en',
	): OpenApi;

}
