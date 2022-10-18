<?php declare(strict_types = 1);

/**
 * ActionConnectorProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\Actions;

use FastyBird\Library\Metadata\Exceptions;
use Ramsey\Uuid;
use function array_merge;

/**
 * Connector property action entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionConnectorProperty extends ActionProperty
{

	private Uuid\UuidInterface $connector;

	public function __construct(
		string $action,
		string $connector,
		string $property,
		float|bool|int|string|null $expectedValue = null,
	)
	{
		parent::__construct($action, $property, $expectedValue);

		$this->connector = Uuid\Uuid::fromString($connector);
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'connector' => $this->getConnector()->toString(),
		]);
	}

	/**
	 * @return Array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
