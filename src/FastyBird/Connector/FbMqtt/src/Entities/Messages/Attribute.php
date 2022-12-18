<?php declare(strict_types = 1);

/**
 * Attribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           24.02.20
 */

namespace FastyBird\Connector\FbMqtt\Entities\Messages;

use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use Ramsey\Uuid;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * Device, channel or property attribute
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Attribute extends Entity
{

	public const NAME = 'name';

	public const PROPERTIES = 'properties';

	public const STATE = 'state';

	public const CHANNELS = 'channels';

	public const EXTENSIONS = 'extensions';

	public const CONTROLS = 'controls';

	/** @var string|array<string> */
	private string|array $value;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		private readonly string $attribute,
		string $value,
	)
	{
		if (!in_array($attribute, $this->getAllowedAttributes(), true)) {
			throw new Exceptions\InvalidArgument(
				sprintf('Provided attribute "%s" is not in allowed range', $attribute),
			);
		}

		parent::__construct($connector, $device);

		$this->parseValue($value);
	}

	public function getAttribute(): string
	{
		return $this->attribute;
	}

	/**
	 * @return string|array<string>
	 */
	public function getValue(): array|string
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			$this->getAttribute() => $this->getValue(),
		], parent::toArray());
	}

	/**
	 * @return array<string>
	 */
	protected function getAllowedAttributes(): array
	{
		return [];
	}

	private function parseValue(string $value): void
	{
		if ($this->getAttribute() === self::NAME) {
			$this->value = Helpers\Payload::cleanName($value);

		} else {
			$this->value = Helpers\Payload::cleanPayload($value);

			if (
				$this->getAttribute() === self::PROPERTIES
				|| $this->getAttribute() === self::CHANNELS
				|| $this->getAttribute() === self::EXTENSIONS
				|| $this->getAttribute() === self::CONTROLS
			) {
				$items = array_filter(
					array_map('trim', explode(',', strtolower($value))),
					static fn ($item): bool => $item !== '',
				);

				$items = array_values($items);

				$this->value = array_unique($items);
			}
		}
	}

}
