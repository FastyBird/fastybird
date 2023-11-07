<?php declare(strict_types = 1);

/**
 * AccountEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           18.08.20
 */

namespace FastyBird\Module\Accounts\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\SimpleAuth;
use Nette;
use function array_merge;
use function count;
use function in_array;
use function sprintf;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AccountEntity implements Common\EventSubscriber
{

	use Nette\SmartObject;

	/** @var array<string> */
	private array $singleRoles = [
		SimpleAuth\Constants::ROLE_ADMINISTRATOR,
		SimpleAuth\Constants::ROLE_USER,
	];

	/** @var array<string> */
	private array $notAssignableRoles = [
		SimpleAuth\Constants::ROLE_VISITOR,
		SimpleAuth\Constants::ROLE_ANONYMOUS,
	];

	public function __construct(
		private readonly Models\Entities\Accounts\AccountsRepository $accountsRepository,
		private readonly Models\Entities\Roles\RolesRepository $rolesRepository,
	)
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::prePersist,
			ORM\Events::onFlush,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function prePersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach ($uow->getScheduledEntityInsertions() as $object) {
			if (
				$object instanceof Entities\Accounts\Account
				&& $this->getAdministrator() === null
				&& !$object->hasRole(SimpleAuth\Constants::ROLE_ADMINISTRATOR)
			) {
				throw new Exceptions\InvalidState('First account have to be an administrator account');
			}
		}
	}

	/**
	 * @throws Exceptions\AccountRoleInvalid
	 * @throws Exceptions\InvalidState
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach (array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()) as $object) {
			if (!$object instanceof Entities\Accounts\Account) {
				continue;
			}

			/**
			 * If new account is without any role
			 * we have to assign default roles
			 */
			if (count($object->getRoles()) === 0) {
				$object->setRoles($this->getDefaultRoles(Accounts\Constants::USER_ACCOUNT_DEFAULT_ROLES));
			}

			foreach ($object->getRoles() as $role) {
				/**
				 * Special roles like administrator or user
				 * can not be assigned to account with other roles
				 */
				if (
					in_array($role->getName(), $this->singleRoles, true)
					&& count($object->getRoles()) > 1
				) {
					throw new Exceptions\AccountRoleInvalid(
						sprintf('Role %s could not be combined with other roles', $role->getName()),
					);
				}

				/**
				 * Special roles like visitor or guest
				 * can not be assigned to account
				 */
				if (in_array($role->getName(), $this->notAssignableRoles, true)) {
					throw new Exceptions\AccountRoleInvalid(
						sprintf('Role %s could not be assigned to account', $role->getName()),
					);
				}
			}
		}
	}

	/**
	 * @param array<string> $roleNames
	 *
	 * @return array<Entities\Roles\Role>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function getDefaultRoles(array $roleNames): array
	{
		$roles = [];

		foreach ($roleNames as $roleName) {
			$findRole = new Queries\Entities\FindRoles();
			$findRole->byName($roleName);

			$role = $this->rolesRepository->findOneBy($findRole);

			if ($role === null) {
				throw new Exceptions\InvalidState(sprintf('Role %s is not created', $roleName));
			}

			$roles[] = $role;
		}

		return $roles;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getAdministrator(): Entities\Accounts\Account|null
	{
		$findRole = new Queries\Entities\FindRoles();
		$findRole->byName(SimpleAuth\Constants::ROLE_ADMINISTRATOR);

		$role = $this->rolesRepository->findOneBy($findRole);

		if ($role === null) {
			throw new Exceptions\InvalidState(
				sprintf('Role %s is not created', SimpleAuth\Constants::ROLE_ADMINISTRATOR),
			);
		}

		$findAccount = new Queries\Entities\FindAccounts();
		$findAccount->inRole($role);

		return $this->accountsRepository->findOneBy($findAccount);
	}

}
