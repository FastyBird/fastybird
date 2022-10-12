<?php declare(strict_types = 1);

/**
 * TChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Metadata\Entities\DevicesModule;

use Ramsey\Uuid;

/**
 * Channel property trait
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
trait TChannelProperty
{

	protected Uuid\UuidInterface $channel;

	protected Uuid\UuidInterface|null $parent;

	/** @var array<Uuid\UuidInterface> */
	protected array $children;

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getParent(): Uuid\UuidInterface|null
	{
		return $this->parent;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

}
