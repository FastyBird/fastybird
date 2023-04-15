<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\Models;

use FastyBird\Bridge\RedisDbDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;

/**
 * Connector property states manager
 *
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertiesManager implements DevicesModels\States\IConnectorPropertiesManager
{

	use Nette\SmartObject;

	/** @var RedisDbModels\StatesManager<States\ConnectorProperty> */
	private RedisDbModels\StatesManager $statesManager;

	/**
	 * @phpstan-param  RedisDbModels\StatesManagerFactory<States\ConnectorProperty> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\ConnectorProperty::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ConnectorProperty
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(
		DevicesStates\ConnectorProperty $state,
		Utils\ArrayHash $values,
	): States\ConnectorProperty
	{
		assert($state instanceof States\ConnectorProperty);

		return $this->statesManager->update($state, $values, $this->database);
	}

	public function delete(DevicesStates\ConnectorProperty $state): bool
	{
		assert($state instanceof States\ConnectorProperty);

		return $this->statesManager->delete($state, $this->database);
	}

}
