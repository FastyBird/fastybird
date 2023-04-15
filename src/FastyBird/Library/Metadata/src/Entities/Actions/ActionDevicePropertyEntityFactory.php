<?php declare(strict_types = 1);

/**
 * ActionDevicePropertyEntityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\Actions;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Schemas;
use Nette\Utils;
use function is_string;

/**
 * Device property action entity factory
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionDevicePropertyEntityFactory extends Entities\EntityFactory
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
	public function create(string|array|Utils\ArrayHash $data): ActionDeviceProperty
	{
		if (is_string($data)) {
			$schema = $this->loader->loadByNamespace('schemas/actions', 'action.device.property.json');

			$data = $this->validator->validate($data, $schema);

		} elseif (!$data instanceof Utils\ArrayHash) {
			$data = Utils\ArrayHash::from($data);
		}

		$entity = $this->build(ActionDeviceProperty::class, $data);

		if ($entity instanceof ActionDeviceProperty) {
			return $entity;
		}

		throw new Exceptions\InvalidState('Transformer could not be created');
	}

}
