<?php declare(strict_types = 1);

/**
 * ConnectorConnection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          0.73.0
 *
 * @date           19.07.22
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Connector connection states manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorConnection
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Connectors\Properties\PropertiesManager $manager,
		private readonly ConnectorPropertiesStates $propertiesStates,
	)
	{
	}

	public function setState(
		Entities\Connectors\Connector $connector,
		MetadataTypes\ConnectionState $state,
	): bool
	{
		$property = $connector->findProperty(MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE);

		if ($property === null) {
			$property = $this->manager->create(Utils\ArrayHash::from([
				'connector' => $connector,
				'entity' => Entities\Connectors\Properties\Dynamic::class,
				'identifier' => MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => [
					MetadataTypes\ConnectionState::STATE_RUNNING,
					MetadataTypes\ConnectionState::STATE_STOPPED,
					MetadataTypes\ConnectionState::STATE_UNKNOWN,
					MetadataTypes\ConnectionState::STATE_SLEEPING,
					MetadataTypes\ConnectionState::STATE_ALERT,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}

		assert($property instanceof Entities\Connectors\Properties\Dynamic);

		$this->propertiesStates->setValue(
			$property,
			Utils\ArrayHash::from([
				'actualValue' => $state->getValue(),
				'expectedValue' => null,
				'pending' => false,
				'valid' => true,
			]),
		);

		return false;
	}

	public function getState(
		Entities\Connectors\Connector $connector,
	): MetadataTypes\ConnectionState
	{
		$property = $connector->findProperty(MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE);

		if ($property instanceof Entities\Connectors\Properties\Dynamic) {
			$state = $this->propertiesStates->getValue($property);

			if (
				$state?->getActualValue() !== null
				&& MetadataTypes\ConnectionState::isValidValue($state->getActualValue())
			) {
				return MetadataTypes\ConnectionState::get($state->getActualValue());
			}
		}

		return MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN);
	}

}
