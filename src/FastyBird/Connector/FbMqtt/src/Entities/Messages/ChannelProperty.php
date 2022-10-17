<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           05.03.20
 */

namespace FastyBird\Connector\FbMqtt\Entities\Messages;

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

	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		private readonly string $channel,
		string $property,
	)
	{
		parent::__construct($connector, $device, $property);
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

	public function getChannel(): string
	{
		return $this->channel;
	}

}
