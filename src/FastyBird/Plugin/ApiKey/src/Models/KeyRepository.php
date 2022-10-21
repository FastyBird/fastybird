<?php declare(strict_types = 1);

/**
 * KeyRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Models;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Plugin\ApiKey\Entities;
use Nette;

/**
 * API key repository
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class KeyRepository
{

	use Nette\SmartObject;

	/** @var Array<ORM\EntityRepository<Entities\Key>> */
	private array $repository = [];

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	public function findOneByIdentifier(string $identifier): Entities\Key|null
	{
		return $this->getRepository()->findOneBy(['id' => $identifier]);
	}

	public function findOneByKey(string $key): Entities\Key|null
	{
		return $this->getRepository()->findOneBy(['key' => $key]);
	}

	/**
	 * @phpstan-param class-string<Entities\Key> $type
	 *
	 * @phpstan-return ORM\EntityRepository<Entities\Key>
	 */
	private function getRepository(string $type = Entities\Key::class): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		return $this->repository[$type];
	}

}
