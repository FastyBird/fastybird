<?php declare(strict_types = 1);

/**
 * ConnectorVariableProperty.php
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
use function array_merge;

/**
 * Connector variable property entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorVariableProperty extends VariableProperty
{

	use TConnectorProperty;

	/**
	 * @param Array<int, string>|Array<int, string|int|float|Array<int, string|int|float>|null>|Array<int, Array<int, string|Array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		string $id,
		string $connector,
		string $type,
		string $identifier,
		string|null $name,
		bool $settable,
		bool $queryable,
		string $dataType,
		string|null $unit = null,
		array|null $format = null,
		string|int|float|null $invalid = null,
		int|null $numberOfDecimals = null,
		float|bool|int|string|null $value = null,
		float|bool|int|string|null $default = null,
		string|null $owner = null,
	)
	{
		parent::__construct(
			$id,
			$type,
			$identifier,
			$name,
			$settable,
			$queryable,
			$dataType,
			$unit,
			$format,
			$invalid,
			$numberOfDecimals,
			$value,
			$default,
			$owner,
		);

		$this->connector = Uuid\Uuid::fromString($connector);
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'connector' => $this->getConnector(),
		]);
	}

	/**
	 * @return Array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
