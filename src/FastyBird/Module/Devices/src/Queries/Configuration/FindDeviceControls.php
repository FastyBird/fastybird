<?php declare(strict_types = 1);

/**
 * FindDeviceControls.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           15.11.23
 */

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use Flow\JSONPath;
use Ramsey\Uuid;

/**
 * Find devices controls configuration query
 *
 * @template T of MetadataDocuments\DevicesModule\DeviceControl
 * @extends  QueryObject<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDeviceControls extends QueryObject
{

	/** @var array<string> */
	private array $filter = [];

	public function __construct()
	{
		$this->filter[] = '.[?(@.device != "")]';
	}

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id == ' . $id->toString() . ')]';
	}

	public function byName(string $name): void
	{
		$this->filter[] = '.[?(@.name == ' . $name . ')]';
	}

	public function forDevice(MetadataDocuments\DevicesModule\Device $device): void
	{
		$this->filter[] = '.[?(@.device == ' . $device->getId()->toString() . ')]';
	}

	public function byDeviceId(Uuid\UuidInterface $deviceId): void
	{
		$this->filter[] = '.[?(@.device == ' . $deviceId->toString() . ')]';
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
