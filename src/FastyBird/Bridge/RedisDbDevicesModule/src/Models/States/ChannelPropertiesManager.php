<?php declare(strict_types = 1);

/**
 * ChannelPropertiesManager.php
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

namespace FastyBird\Bridge\RedisDbDevicesModule\Models\States;

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
 * Channel property states manager
 *
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertiesManager implements DevicesModels\States\IChannelPropertiesManager
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesManager<States\ChannelProperty> */
	private RedisDbModels\States\StatesManager $statesManager;

	/**
	 * @phpstan-param RedisDbModels\States\StatesManagerFactory<States\ChannelProperty> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\ChannelProperty::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ChannelProperty
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(DevicesStates\ChannelProperty $state, Utils\ArrayHash $values): States\ChannelProperty
	{
		assert($state instanceof States\ChannelProperty);

		return $this->statesManager->update($state, $values, $this->database);
	}

	public function delete(DevicesStates\ChannelProperty $state): bool
	{
		assert($state instanceof States\ChannelProperty);

		return $this->statesManager->delete($state, $this->database);
	}

}
