<?php declare(strict_types = 1);

/**
 * FindDeviceMappedProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           16.11.23
 */

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Documents;

/**
 * Find device mapped properties entities query
 *
 * @template T of Documents\Devices\Properties\Mapped
 * @extends  FindDeviceProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDeviceMappedProperties extends FindDeviceProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . MetadataTypes\PropertyType::MAPPED . '")]';
	}

}
