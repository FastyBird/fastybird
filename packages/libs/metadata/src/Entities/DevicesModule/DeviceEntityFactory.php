<?php declare(strict_types = 1);

/**
 * DeviceEntityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           04.06.22
 */

namespace FastyBird\Metadata\Entities\DevicesModule;

use FastyBird\Metadata\Entities;
use FastyBird\Metadata\Exceptions;
use FastyBird\Metadata\Loaders;
use FastyBird\Metadata\Schemas;
use Nette\Utils;
use function is_string;

/**
 * Device entity factory
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceEntityFactory extends Entities\EntityFactory
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
	public function create(string|array|Utils\ArrayHash $data): Device
	{
		if (is_string($data)) {
			$schema = $this->loader->loadByNamespace('schemas/modules/devices-module', 'entity.device.json');

			$data = $this->validator->validate($data, $schema);

		} elseif (!$data instanceof Utils\ArrayHash) {
			$data = Utils\ArrayHash::from($data);
		}

		$entity = $this->build(Device::class, $data);

		if ($entity instanceof Device) {
			return $entity;
		}

		throw new Exceptions\InvalidState('Entity could not be created');
	}

}
