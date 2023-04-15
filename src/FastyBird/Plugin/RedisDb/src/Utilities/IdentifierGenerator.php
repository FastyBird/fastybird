<?php declare(strict_types = 1);

/**
 * IdentifierGenerator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           09.10.22
 */

namespace FastyBird\Plugin\RedisDb\Utilities;

use Nette;
use Ramsey\Uuid;

/**
 * Pub/Sub messages identifier
 *
 * @package        FastyBird:RedisDbPlugin!
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
