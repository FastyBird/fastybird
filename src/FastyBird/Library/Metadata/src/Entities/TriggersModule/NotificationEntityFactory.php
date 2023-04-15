<?php declare(strict_types = 1);

/**
 * NotificationEntityFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Schemas;
use FastyBird\Library\Metadata\Types;
use IPub\Phone;
use Nette\Utils;
use function is_string;
use function strval;

/**
 * Notification entity factory
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NotificationEntityFactory extends Entities\EntityFactory
{

	public function __construct(
		private readonly Loaders\SchemaLoader $loader,
		private readonly Schemas\Validator $validator,
		private readonly Phone\Phone $phone,
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
	 * @throws Phone\Exceptions\NoValidCountryException
	 * @throws Phone\Exceptions\NoValidPhoneException
	 */
	public function create(string|array|Utils\ArrayHash $data): Notification
	{
		if (is_string($data)) {
			$schema = $this->loader->loadByNamespace('schemas/modules/triggers-module', 'entity.notification.json');

			$data = $this->validator->validate($data, $schema);

		} elseif (!$data instanceof Utils\ArrayHash) {
			$data = Utils\ArrayHash::from($data);
		}

		$type = Types\TriggerNotificationType::get($data->offsetGet('type'));

		if ($type->equalsValue(Types\TriggerNotificationType::TYPE_EMAIL)) {
			$entity = $this->build(EmailNotification::class, $data);

		} elseif ($type->equalsValue(Types\TriggerNotificationType::TYPE_SMS)) {
			$phone = null;

			if ($data->offsetExists('phone')) {
				$phone = $data->offsetGet('phone');

				$data->offsetUnset('phone');
			}

			$entity = $this->build(SmsNotification::class, $data);

			if ($entity instanceof SmsNotification) {
				if ($phone !== null) {
					$entity->setPhone($this->phone->parse(strval($phone)));

				} else {
					throw new Exceptions\InvalidState('Transformer could not be created');
				}
			}
		} else {
			throw new Exceptions\InvalidArgument('Provided data and routing key is for unsupported notification type');
		}

		if ($entity instanceof Notification) {
			return $entity;
		}

		throw new Exceptions\InvalidState('Transformer could not be created');
	}

}
