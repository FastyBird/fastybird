<?php declare(strict_types = 1);

/**
 * ActionEntityFactory.php
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

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Schemas;
use FastyBird\Library\Metadata\Types;
use Nette\Utils;
use function is_string;

/**
 * Channel property entity factory
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertyEntityFactory extends Entities\EntityFactory
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
	public function create(string|array|Utils\ArrayHash $data): Property
	{
		if (is_string($data)) {
			$schema = $this->loader->loadByNamespace('schemas/modules/devices-module', 'entity.channel.property.json');

			$data = $this->validator->validate($data, $schema);

		} elseif (!$data instanceof Utils\ArrayHash) {
			$data = Utils\ArrayHash::from($data);
		}

		$type = Types\PropertyType::get($data->offsetGet('type'));

		if ($type->equalsValue(Types\PropertyType::TYPE_DYNAMIC)) {
			$entity = $this->build(ChannelDynamicProperty::class, $data);

		} elseif ($type->equalsValue(Types\PropertyType::TYPE_VARIABLE)) {
			$entity = $this->build(ChannelVariableProperty::class, $data);

		} elseif ($type->equalsValue(Types\PropertyType::TYPE_MAPPED)) {
			$entity = $this->build(ChannelMappedProperty::class, $data);

		} else {
			throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported property type');
		}

		if ($entity instanceof Property) {
			return $entity;
		}

		throw new Exceptions\InvalidState('Entity could not be created');
	}

}
