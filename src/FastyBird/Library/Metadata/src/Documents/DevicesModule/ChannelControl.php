<?php declare(strict_types = 1);

/**
 * ChannelControl.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Documents\DevicesModule;

use DateTimeInterface;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Channel control document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelControl extends Control
{

	public function __construct(
		Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $channel,
		string $name,
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id, $name, $owner, $createdAt, $updatedAt);
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel()->toString(),
		]);
	}

}
