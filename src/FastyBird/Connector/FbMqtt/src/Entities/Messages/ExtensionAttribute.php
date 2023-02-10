<?php declare(strict_types = 1);

/**
 * ExtensionAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\Entities\Messages;

use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Types;
use Ramsey\Uuid;
use function array_merge;
use function in_array;
use function sprintf;

/**
 * Device extension attribute
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExtensionAttribute extends Entity
{

	public const MAC_ADDRESS = 'mac-address';

	public const MANUFACTURER = 'manufacturer';

	public const MODEL = 'model';

	public const VERSION = 'version';

	public const NAME = 'name';

	public const ALLOWED_PARAMETERS = [
		self::MAC_ADDRESS,
		self::MANUFACTURER,
		self::MODEL,
		self::VERSION,
		self::NAME,
	];

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		private readonly Types\ExtensionType $extension,
		private readonly string $parameter,
		private readonly string $value,
	)
	{
		if (!in_array($parameter, self::ALLOWED_PARAMETERS, true)) {
			throw new Exceptions\InvalidArgument(
				sprintf('Provided extension attribute "%s" is not in allowed range', $parameter),
			);
		}

		parent::__construct($connector, $device);
	}

	public function getExtension(): Types\ExtensionType
	{
		return $this->extension;
	}

	public function getParameter(): string
	{
		return $this->parameter;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			$this->getParameter() => $this->getValue(),
		], parent::toArray());
	}

}
