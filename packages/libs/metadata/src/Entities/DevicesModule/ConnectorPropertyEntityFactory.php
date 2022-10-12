<?php declare(strict_types = 1);

/**
 * ActionEntityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Metadata\Entities\DevicesModule;

use FastyBird\Metadata\Entities;
use FastyBird\Metadata\Exceptions;
use FastyBird\Metadata\Loaders;
use FastyBird\Metadata\Schemas;
use FastyBird\Metadata\Types;
use Nette\Utils;
use function is_string;

/**
 * Connector property entity factory
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertyEntityFactory extends Entities\EntityFactory
{

	public function __construct(
		private readonly Loaders\SchemaLoader $loader,
		private readonly Schemas\Validator $validator,
	)
	{
	}

	/**
	 * @param string|Array<string, mixed>|Utils\ArrayHash<string> $data
	 *
	 * @throws Exceptions\FileNotFound
	 */
	public function create(string|array|Utils\ArrayHash $data): Property
	{
		if (is_string($data)) {
			$schema = $this->loader->loadByNamespace(
				'schemas/modules/devices-module',
				'entity.connector.property.json',
			);

			$data = $this->validator->validate($data, $schema);

		} elseif (!$data instanceof Utils\ArrayHash) {
			$data = Utils\ArrayHash::from($data);
		}

		$type = Types\PropertyType::get($data->offsetGet('type'));

		if ($type->equalsValue(Types\PropertyType::TYPE_DYNAMIC)) {
			$entity = $this->build(ConnectorDynamicProperty::class, $data);

		} elseif ($type->equalsValue(Types\PropertyType::TYPE_VARIABLE)) {
			$entity = $this->build(ConnectorVariableProperty::class, $data);

		} elseif ($type->equalsValue(Types\PropertyType::TYPE_MAPPED)) {
			$entity = $this->build(ConnectorMappedProperty::class, $data);

		} else {
			throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported property type');
		}

		if ($entity instanceof Property) {
			return $entity;
		}

		throw new Exceptions\InvalidState('Entity could not be created');
	}

}
