<?php declare(strict_types = 1);

/**
 * FindChannelProperties.php
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

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use Flow\JSONPath;
use Ramsey\Uuid;

/**
 * Find channels properties configuration query
 *
 * @template T of MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelVariableProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty
 * @extends  QueryObject<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannelProperties extends QueryObject
{

	/** @var array<string> */
	protected array $filter = [];

	public function __construct()
	{
		$this->filter[] = '.[?(@.channel != "")]';
	}

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

	public function forChannel(MetadataDocuments\DevicesModule\Channel $channel): void
	{
		$this->filter[] = '.[?(@.channel == ' . $channel->getId()->toString() . ')]';
	}

	public function byChannelId(Uuid\UuidInterface $channelId): void
	{
		$this->filter[] = '.[?(@.channel == ' . $channelId->toString() . ')]';
	}

	public function forParent(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelVariableProperty $parent,
	): void
	{
		$this->filter[] = '.[?(@.parent == ' . $parent->getId()->toString() . ')]';
	}

	public function byParentId(Uuid\UuidInterface $parentId): void
	{
		$this->filter[] = '.[?(@.parent == ' . $parentId->toString() . ')]';
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
