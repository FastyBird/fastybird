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

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Documents;

/**
 * Find connector variable properties entities query
 *
 * @template T of Documents\Connectors\Properties\Variable
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

		$this->filter[] = '.[?(@.type == "' . MetadataTypes\PropertyType::VARIABLE . '")]';
	}

	public function byValue(string $value): void
	{
		$this->filter[] = '.[?(@.value =~ /(?i).*^' . $value . '*$/)]';
	}

}
