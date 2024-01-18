<?php declare(strict_types = 1);

/**
 * IManager.php
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
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Connector properties manager interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IManager
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ConnectorProperty;

	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ConnectorProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
