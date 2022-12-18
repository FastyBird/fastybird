<?php declare(strict_types = 1);

/**
 * ConditionEntityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Schemas;
use FastyBird\Library\Metadata\Types;
use Nette\Utils;
use function is_string;

/**
 * Condition entity factory
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConditionEntityFactory extends Entities\EntityFactory
{

	public function __construct(
		private readonly Loaders\SchemaLoader $loader,
		private readonly Schemas\Validator $validator,
	)
	{
	}

	/**
	 * @param string|array<string, mixed>|Utils\ArrayHash<string> $data
	 *
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidData
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Logic
	 * @throws Exceptions\MalformedInput
	 */
	public function create(string|array|Utils\ArrayHash $data): Condition
	{
		if (is_string($data)) {
			$schema = $this->loader->loadByNamespace('schemas/modules/triggers-module', 'entity.condition.json');

			$data = $this->validator->validate($data, $schema);

		} elseif (!$data instanceof Utils\ArrayHash) {
			$data = Utils\ArrayHash::from($data);
		}

		if ($data->offsetGet('type') === Types\TriggerConditionType::TYPE_DEVICE_PROPERTY) {
			$entity = $this->build(DevicePropertyCondition::class, $data);

		} elseif ($data->offsetGet('type') === Types\TriggerConditionType::TYPE_CHANNEL_PROPERTY) {
			$entity = $this->build(ChannelPropertyCondition::class, $data);

		} elseif ($data->offsetGet('type') === Types\TriggerConditionType::TYPE_TIME) {
			$entity = $this->build(TimeCondition::class, $data);

		} elseif ($data->offsetGet('type') === Types\TriggerConditionType::TYPE_DATE) {
			$entity = $this->build(DateCondition::class, $data);

		} else {
			$entity = $this->build(Condition::class, $data);
		}

		if ($entity instanceof Condition) {
			return $entity;
		}

		throw new Exceptions\InvalidState('Entity could not be created');
	}

}
