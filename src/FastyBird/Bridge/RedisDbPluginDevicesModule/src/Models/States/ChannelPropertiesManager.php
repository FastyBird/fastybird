<?php declare(strict_types = 1);

/**
 * ChannelPropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Channel property states manager
 *
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertiesManager implements DevicesModels\States\Channels\IManager
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesManager<States\ChannelProperty> */
	private RedisDbModels\States\StatesManager $statesManager;

	/**
	 * @param RedisDbModels\States\StatesManagerFactory<States\ChannelProperty> $statesManagerFactory
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
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ChannelProperty
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ChannelProperty|false
	{
		return $this->statesManager->update($id, $values, $this->database);
	}

	public function delete(Uuid\UuidInterface $id): bool
	{
		return $this->statesManager->delete($id, $this->database);
	}

}
