<?php declare(strict_types = 1);

/**
 * ConnectorDynamicProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use FastyBird\Library\Metadata\Exceptions;
use Ramsey\Uuid;
use function array_merge;

/**
 * Connector dynamic property entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorDynamicProperty extends DynamicProperty
{

	private Uuid\UuidInterface $connector;

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 * @param bool|null $pending
	 */
	public function __construct(
		string $id,
		string $connector,
		string $type,
		string $category,
		string $identifier,
		string|null $name,
		bool $settable,
		bool $queryable,
		string $dataType,
		string|null $unit = null,
		array|null $format = null,
		string|int|float|null $invalid = null,
		int|null $scale = null,
		float|null $step = null,
		float|bool|int|string|null $actualValue = null,
		float|bool|int|string|null $previousValue = null,
		float|bool|int|string|null $expectedValue = null,
		bool|string|null $pending = null,
		bool|null $valid = null,
		string|null $owner = null,
	)
	{
		parent::__construct(
			$id,
			$type,
			$category,
			$identifier,
			$name,
			$settable,
			$queryable,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
			$actualValue,
			$previousValue,
			$expectedValue,
			$pending,
			$valid,
			$owner,
		);

		$this->connector = Uuid\Uuid::fromString($connector);
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'connector' => $this->getConnector()->toString(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
