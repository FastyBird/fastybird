<?php declare(strict_types = 1);

/**
 * ChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           29.06.23
 */

namespace FastyBird\Connector\Viera\Entities\Messages;

use Ramsey\Uuid;
use function array_merge;

/**
 * Channel property state message entity
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertyState extends Device
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		private readonly string $property,
		private readonly int|bool $state,
	)
	{
		parent::__construct($connector, $identifier);
	}

	public function getProperty(): string
	{
		return $this->property;
	}

	public function getState(): int|bool
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'property' => $this->getProperty(),
			'state' => $this->getState(),
		]);
	}

}
