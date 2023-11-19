<?php declare(strict_types = 1);

/**
 * ServiceFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           13.09.22
 */

namespace FastyBird\Connector\HomeKit\Entities\Protocol;

use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use Nette;
use Nette\Utils;
use function is_string;
use function sprintf;
use function strval;

/**
 * HAP service factory
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ServiceFactory
{

	public function __construct(private readonly Helpers\Loader $loader)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function create(
		Types\ServiceType $type,
		Accessory $accessory,
		MetadataDocuments\DevicesModule\Channel|null $channel = null,
	): Service
	{
		$metadata = $this->loader->loadServices();

		if (!$metadata->offsetExists(strval($type->getValue()))) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for service: %s was not found',
				strval($type->getValue()),
			));
		}

		$serviceMetadata = $metadata->offsetGet(strval($type->getValue()));

		if (
			!$serviceMetadata instanceof Utils\ArrayHash
			|| !$serviceMetadata->offsetExists('UUID')
			|| !is_string($serviceMetadata->offsetGet('UUID'))
			|| !$serviceMetadata->offsetExists('RequiredCharacteristics')
			|| !$serviceMetadata->offsetGet('RequiredCharacteristics') instanceof Utils\ArrayHash
		) {
			throw new Exceptions\InvalidState('Service definition is missing required attributes');
		}

		return new Service(
			Helpers\Protocol::hapTypeToUuid(strval($serviceMetadata->offsetGet('UUID'))),
			strval($type->getValue()),
			$accessory,
			$channel,
			(array) $serviceMetadata->offsetGet('RequiredCharacteristics'),
			$serviceMetadata->offsetExists('OptionalCharacteristics') && $serviceMetadata->offsetGet(
				'OptionalCharacteristics',
			) instanceof Utils\ArrayHash ? (array) $serviceMetadata->offsetGet(
				'OptionalCharacteristics',
			) : [],
			$serviceMetadata->offsetExists('VirtualCharacteristics') && $serviceMetadata->offsetGet(
				'VirtualCharacteristics',
			) instanceof Utils\ArrayHash ? (array) $serviceMetadata->offsetGet(
				'VirtualCharacteristics',
			) : [],
		);
	}

}
