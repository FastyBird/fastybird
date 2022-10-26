<?php declare(strict_types = 1);

/**
 * IChannelPropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.9.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Module\Devices\Models\States;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Channel properties manager interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IChannelPropertiesManager
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ChannelProperty;

	public function update(States\ChannelProperty $state, Utils\ArrayHash $values): States\ChannelProperty;

	public function delete(States\ChannelProperty $state): bool;

}
