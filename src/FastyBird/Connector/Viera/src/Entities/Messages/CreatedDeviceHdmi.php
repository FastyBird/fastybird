<?php declare(strict_types = 1);

/**
 * CreatedDeviceHdmi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           28.06.23
 */

namespace FastyBird\Connector\Viera\Entities\Messages;

use Nette;

/**
 * Created device hdmi entity
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CreatedDeviceHdmi implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $id,
		private readonly string $name,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
		];
	}

}
