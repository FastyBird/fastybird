<?php declare(strict_types = 1);

/**
 * Sign.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Nette;

/**
 * OpenAPI sign entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Sign implements Entity
{

	use Nette\SmartObject;

	public function __construct(private readonly string $sign, private readonly int $timestamp)
	{
	}

	public function getSign(): string
	{
		return $this->sign;
	}

	public function getTimestamp(): int
	{
		return $this->timestamp;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'sign' => $this->getSign(),
			'timestamp' => $this->getTimestamp(),
		];
	}

}
