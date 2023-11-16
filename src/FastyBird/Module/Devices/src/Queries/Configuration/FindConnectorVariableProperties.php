<?php declare(strict_types = 1);

/**
 * FindConnectorMappedProperties.php
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

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Find connector variable properties entities query
 *
 * @template T of MetadataEntities\DevicesModule\ConnectorVariableProperty
 * @extends  FindConnectorProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConnectorVariableProperties extends FindConnectorProperties
{

	public function __construct()
	{
		parent::__construct();

		$this->filter[] = '.[?(@.type == "' . MetadataTypes\PropertyType::TYPE_VARIABLE . '")]';
	}

}
