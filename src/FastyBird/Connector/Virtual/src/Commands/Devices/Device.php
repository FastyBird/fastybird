<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Connector\Virtual\Commands\Devices;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Virtual\Exceptions;
use Symfony\Component\Console;

/**
 * Connector device management command
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends Console\Command\Command
{

	public const ACTION_CREATE = 'create';

	public const ACTION_EDIT = 'edit';

	public const ACTION_MANAGE = 'manage';

	public function __construct(
		private readonly Persistence\ManagerRegistry $managerRegistry,
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	protected function getOrmConnection(): DBAL\Connection
	{
		$connection = $this->managerRegistry->getConnection();

		if ($connection instanceof DBAL\Connection) {
			return $connection;
		}

		throw new Exceptions\Runtime('Database connection could not be established');
	}

}
