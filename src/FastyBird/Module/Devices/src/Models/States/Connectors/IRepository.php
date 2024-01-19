<?php declare(strict_types = 1);

/**
 * IRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Models\States\Connectors;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;

/**
 * Connector property repository interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IRepository
{

	public function find(
		Uuid\UuidInterface $id,
	): States\ConnectorProperty|null;

}
