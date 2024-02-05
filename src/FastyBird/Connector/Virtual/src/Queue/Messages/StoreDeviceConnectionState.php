<?php declare(strict_types = 1);

/**
 * StoreDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           17.10.23
 */

namespace FastyBird\Connector\Virtual\Queue\Messages;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Device state message
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState implements Message
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\ConnectionState::class)]
		private readonly MetadataTypes\ConnectionState $state,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\Sources\Connector::class),
			new ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\Sources\Addon::class),
		])]
		private readonly MetadataTypes\Sources\Source $source,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getState(): MetadataTypes\ConnectionState
	{
		return $this->state;
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return $this->source;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'device' => $this->getDevice()->toString(),
			'state' => $this->getState()->getValue(),
			'source' => $this->getSource()->getValue(),
		];
	}

}
