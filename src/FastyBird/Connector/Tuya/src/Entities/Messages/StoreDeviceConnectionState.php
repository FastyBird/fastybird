<?php declare(strict_types = 1);

/**
 * StoreDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Entities\Messages;

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device state message entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState extends Device
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\ConnectionState::class)]
		private readonly MetadataTypes\ConnectionState $state,
	)
	{
		parent::__construct($connector, $identifier);
	}

	public function getState(): MetadataTypes\ConnectionState
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'state' => $this->getState()->getValue(),
		]);
	}

}
