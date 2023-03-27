<?php declare(strict_types = 1);

/**
 * IdentifierGenerator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           26.3.23
 */

namespace FastyBird\Plugin\RabbitMq\Utilities;

use Nette;
use Ramsey\Uuid;

/**
 * Pub/Sub messages identifier
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class IdentifierGenerator
{

	use Nette\SmartObject;

	private string $identifier;

	public function __construct()
	{
		$this->identifier = Uuid\Uuid::uuid4()->toString();
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

}
