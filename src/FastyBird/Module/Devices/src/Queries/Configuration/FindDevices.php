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

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Ramsey\Uuid;
use function count;
use function implode;
use function serialize;

/**
 * Find devices configuration query
 *
 * @template T of Documents\Devices\Device
 * @extends  QueryObject<T>
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
		$this->filter[] = '.[?(@.id =~ /(?i).*^' . $id->toString() . '*$/)]';
	}

	/**
	 * @interal
	 */
	public function byType(string|int $type): void
	{
		$this->filter[] = '.[?(@.type =~ /(?i).*^' . $type . '*$/)]';
	}

	/**
	 * @param array<string> $types
	 */
	public function byTypes(array $types): void
	{
		$this->filter[] = '.[?(@.type in [' . (count($types) > 0 ? ('"' . implode('","', $types) . '"') : '') . '])]';
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i).*^' . $identifier . '*$/)]';
	}

	public function startWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i).*^' . $identifier . '*[\w\d\-_]+$/)]';
	}

	public function endWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /^[\w\d\-_]+(?i).*' . $identifier . '*$/)]';
	}

	public function forConnector(Documents\Connectors\Connector $connector): void
	{
		$this->filter[] = '.[?(@.connector =~ /(?i).*^' . $connector->getId()->toString() . '*$/)]';
	}

	public function byConnectorId(Uuid\UuidInterface $connectorId): void
	{
		$this->filter[] = '.[?(@.connector =~ /(?i).*^' . $connectorId->toString() . '*$/)]';
	}

	public function forParent(Documents\Devices\Device $parent): void
	{
		$this->filter[] = '.[?(@.parents in "' . $parent->getId()->toString() . '")]';
	}

	public function withoutParents(): void
	{
		$this->filter[] = '.[?(@.parents == 0)]';
	}

	public function withParents(): void
	{
		$this->filter[] = '.[?(@.parents > 0)]';
	}

	public function forChild(Documents\Devices\Device $child): void
	{
		$this->filter[] = '.[?(@.children in "' . $child->getId()->toString() . '")]';
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function withSettableChannelProperties(string $deviceIdentifier): void
	{
		throw new Exceptions\NotImplemented(
			'Query by "withSettableChannelProperties" is not supported by this type of repository',
		);
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

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toString(): string
	{
		try {
			return serialize(Utils\Json::encode($this->filter));
		} catch (Utils\JsonException) {
			throw new Exceptions\InvalidState('Cache key could not be generated');
		}
	}

}
