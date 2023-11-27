<?php declare(strict_types = 1);

/**
 * FindDeviceProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           16.11.23
 */

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Ramsey\Uuid;
use function serialize;

/**
 * Find devices properties configuration query
 *
 * @template T of MetadataDocuments\DevicesModule\DeviceProperty
 * @extends  QueryObject<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDeviceProperties extends QueryObject
{

	/** @var array<string> */
	protected array $filter = [];

	public function __construct()
	{
		$this->filter[] = '.[?(@.device != "")]';
	}

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id =~ /(?i).*^' . $id->toString() . '*$/)]';
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

	public function forDevice(MetadataDocuments\DevicesModule\Device $device): void
	{
		$this->filter[] = '.[?(@.device =~ /(?i).*^' . $device->getId()->toString() . '*$/)]';
	}

	public function byDeviceId(Uuid\UuidInterface $deviceId): void
	{
		$this->filter[] = '.[?(@.device =~ /(?i).*^' . $deviceId->toString() . '*$/)]';
	}

	public function forParent(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceVariableProperty $parent,
	): void
	{
		$this->filter[] = '.[?(@.parent =~ /(?i).*^' . $parent->getId()->toString() . '*$/)]';
	}

	public function byParentId(Uuid\UuidInterface $parentId): void
	{
		$this->filter[] = '.[?(@.parent =~ /(?i).*^' . $parentId->toString() . '*$/)]';
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
