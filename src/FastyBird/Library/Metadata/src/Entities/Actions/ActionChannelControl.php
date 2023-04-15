<?php declare(strict_types = 1);

/**
 * ActionChannelControl.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           31.05.22
 */

namespace FastyBird\Library\Metadata\Entities\Actions;

use Ramsey\Uuid;
use function array_merge;

/**
 * Channel control action entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionChannelControl extends Action
{

	private Uuid\UuidInterface $device;

	private Uuid\UuidInterface $channel;

	public function __construct(
		string $action,
		string $device,
		string $channel,
		string $control,
		float|bool|int|string|null $expectedValue = null,
	)
	{
		parent::__construct($action, $control, $expectedValue);

		$this->device = Uuid\Uuid::fromString($device);
		$this->channel = Uuid\Uuid::fromString($channel);
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'channel' => $this->getChannel()->toString(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
