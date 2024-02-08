<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           05.03.20
 */

namespace FastyBird\Connector\FbMqtt\Queue\Messages;

use FastyBird\Connector\FbMqtt;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device or channel property
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty extends Property
{

	/**
	 * @param array<PropertyAttribute> $attributes
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $channel,
		string $property,
		array $attributes = [],
		string|null $value = FbMqtt\Constants::VALUE_NOT_SET,
		bool $retained = false,
	)
	{
		parent::__construct($connector, $device, $property, $attributes, $value, $retained);
	}

	public function getChannel(): string
	{
		return $this->channel;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'channel' => $this->getChannel(),
		], parent::toArray());
	}

}
