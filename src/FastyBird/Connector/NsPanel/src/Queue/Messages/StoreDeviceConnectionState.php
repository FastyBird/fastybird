<?php declare(strict_types = 1);

/**
 * StoreDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Messages;

use FastyBird\Module\Devices\Types as DevicesTypes;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Store device connection state message
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState extends Device implements Message
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[ObjectMapper\Rules\InstanceOfValue(type: DevicesTypes\ConnectionState::class)]
		private readonly DevicesTypes\ConnectionState $state,
	)
	{
		parent::__construct($connector, $identifier);
	}

	public function getState(): DevicesTypes\ConnectionState
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'state' => $this->getState()->value,
		]);
	}

}
