<?php declare(strict_types = 1);

/**
 * MdnsResultStorage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Storages
 * @since          1.0.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\Storages;

use FastyBird\Connector\Shelly\Entities\Clients\MdnsResult;
use Nette;
use SplObjectStorage;
use function serialize;

/**
 * mDNS search results storage
 *
 * @extends SplObjectStorage<MdnsResult, null>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Storages
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MdnsResultStorage extends SplObjectStorage
{

	use Nette\SmartObject;

	/**
	 * @phpstan-param MdnsResult $object
	 */
	public function getHash(object $object): string
	{
		return serialize($object);
	}

}
