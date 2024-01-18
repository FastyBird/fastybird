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
 * @date           09.01.22
 */

namespace FastyBird\Module\Devices\Models\States\Devices;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Device properties manager interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IManager
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\DeviceProperty;

	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\DeviceProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
