<?php declare(strict_types = 1);

/**
 * ThermostatModeDetectFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           16.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Capabilities;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Protocol;
use function array_key_exists;
use function array_map;
use function assert;
use function preg_match;

/**
 * NS panel thermostat mode detect capability factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ThermostatModeDetectFactory implements CapabilityFactory
{

	public function create(
		Documents\Channels\Channel $channel,
		Protocol\Devices\Device $device,
		Mapping\Capabilities\Capability $capabilityMetadata,
	): ThermostatModeDetect
	{
		assert($channel instanceof Documents\Channels\ThermostatModeDetect);

		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches);

		return new ThermostatModeDetect(
			$channel->getId(),
			$capabilityMetadata->getPermission(),
			$device,
			array_key_exists('name', $matches) ? $matches['name'] : null,
			array_map(
				static fn (Mapping\Configurations\Configuration $configuration) => $configuration->getConfiguration(),
				$capabilityMetadata->getConfigurations(),
			),
			[],
			array_map(
				static fn (Mapping\Attributes\Attribute $attribute) => $attribute->getAttribute(),
				$capabilityMetadata->getAttributes(),
			),
			[],
		);
	}

	public function getEntityClass(): string
	{
		return Entities\Channels\ThermostatModeDetect::class;
	}

}
