<?php declare(strict_types = 1);

/**
 * IdentifierGenerator.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Utils
 * @since          0.61.0
 *
 * @date           09.10.22
 */

namespace FastyBird\RedisDbPlugin\Utils;

use Nette;
use Ramsey\Uuid;

/**
 * Pub/Sub messages identifier
 *
 * @package         FastyBird:RedisDbPlugin!
 * @subpackage      Utils
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
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
