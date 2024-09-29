<?php declare(strict_types = 1);

/**
 * Services.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           25.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use Orisai\ObjectMapper;

/**
 * Viera services mapping configuration
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Services implements Mapping\Mapping
{

	/**
	 * @param array<Mapping\Services\Service> $services
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Services\Television::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Services\TelevisionSpeaker::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Services\InputSource::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $services,
	)
	{
	}

	/**
	 * @return array<Mapping\Services\Service>
	 */
	public function getServices(): array
	{
		return $this->services;
	}

}
