<?php declare(strict_types = 1);

/**
 * FindConfigurationChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           26.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Queries\Entities;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Exceptions;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;
use function sprintf;

/**
 * Find device thermostat channels entities query
 *
 * @template T of Entities\Channels\Configuration
 * @extends  VirtualQueries\Entities\FindChannels<T>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConfigurationChannels extends VirtualQueries\Entities\FindChannels
{

	/**
	 * @phpstan-param Types\ChannelIdentifier $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function byIdentifier(Types\ChannelIdentifier|string $identifier): void
	{
		if (!$identifier instanceof Types\ChannelIdentifier) {
			throw new Exceptions\InvalidArgument(
				sprintf('Only instances of: %s are allowed', Types\ChannelIdentifier::class),
			);
		}

		parent::byIdentifier($identifier->value);
	}

}
