<?php declare(strict_types = 1);

/**
 * FindDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           14.11.23
 */

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use Flow\JSONPath;
use Ramsey\Uuid;

/**
 * Find devices configuration query
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDevices extends QueryObject
{

	/** @var array<string> */
	private array $filter = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id == ' . $id->toString() . ')]';
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier == ' . $identifier . ')]';
	}

	public function startWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /^' . $identifier . '[\w\d\-_]+$/)]';
	}

	public function endWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /^[\w\d\-_]+' . $identifier . '$/)]';
	}

	public function byConnectorId(Uuid\UuidInterface $connectorId): void
	{
		$this->filter[] = '.[?(@.connector == ' . $connectorId->toString() . ')]';
	}

	public function forConnector(MetadataEntities\DevicesModule\Connector $connector): void
	{
		$this->filter[] = '.[?(@.connector == ' . $connector->getId()->toString() . ')]';
	}

	public function forParent(MetadataEntities\DevicesModule\Device $parent): void
	{
		$this->filter[] = '.[?(@.parents in "' . $parent->getId()->toString() . '")]';
	}

	public function forChild(MetadataEntities\DevicesModule\Device $child): void
	{
		$this->filter[] = '.[?(@.children in "' . $child->getId()->toString() . '")]';
	}

	public function withChannels(): void
	{
		$this->filter[] = '.[?(@.channels > 0)]';
	}

	/**
	 * @throws JSONPath\JSONPathException
	 */
	protected function doCreateQuery(JSONPath\JSONPath $repository): JSONPath\JSONPath
	{
		$filtered = $repository;

		foreach ($this->filter as $filter) {
			$filtered = $filtered->find($filter);
		}

		return $filtered;
	}

}
