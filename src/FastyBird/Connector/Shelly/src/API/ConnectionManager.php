<?php declare(strict_types = 1);

/**
 * ConnectionManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           23.08.23
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use function array_key_exists;
use function assert;
use function is_string;

/**
 * API connections manager
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectionManager
{

	use Nette\SmartObject;

	private Gen1HttpApi|null $gen1ApiConnection = null;

	private Gen1Coap|null $gen1CoapApiConnection = null;

	private Gen2HttpApi|null $gen2ApiConnection = null;

	/** @var array<string, Gen2WsApi> */
	private array $gen2WsApiConnection = [];

	public function __construct(
		private readonly Gen1HttpApiFactory $gen1HttpApiFactory,
		private readonly Gen1CoapFactory $gen1CoapFactory,
		private readonly Gen2HttpApiFactory $gen2HttpApiFactory,
		private readonly Gen2WsApiFactory $wsApiFactory,
	)
	{
	}

	public function getGen1HttpApiConnection(): Gen1HttpApi
	{
		if ($this->gen1ApiConnection === null) {
			$this->gen1ApiConnection = $this->gen1HttpApiFactory->create();
		}

		return $this->gen1ApiConnection;
	}

	public function getGen1CoapApiConnection(): Gen1Coap
	{
		if ($this->gen1CoapApiConnection === null) {
			$this->gen1CoapApiConnection = $this->gen1CoapFactory->create();
		}

		return $this->gen1CoapApiConnection;
	}

	public function getGen2HttpApiConnection(): Gen2HttpApi
	{
		if ($this->gen2ApiConnection === null) {
			$this->gen2ApiConnection = $this->gen2HttpApiFactory->create();
		}

		return $this->gen2ApiConnection;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getGen2WsApiConnection(Entities\ShellyDevice $device): Gen2WsApi
	{
		if (!array_key_exists($device->getId()->toString(), $this->gen2WsApiConnection)) {
			assert(is_string($device->getIpAddress()));

			$this->gen2WsApiConnection[$device->getId()->toString()] = $this->wsApiFactory->create(
				$device->getIdentifier(),
				$device->getIpAddress(),
				$device->getDomain(),
				$device->getUsername(),
				$device->getPassword(),
			);
		}

		return $this->gen2WsApiConnection[$device->getId()->toString()];
	}

	public function __destruct()
	{
		foreach ($this->gen2WsApiConnection as $key => $connection) {
			$connection->disconnect();

			unset($this->gen2WsApiConnection[$key]);
		}
	}

}
