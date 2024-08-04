<?php declare(strict_types = 1);

/**
 * ConnectorProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Hydrators\Widgets\DataSources;

use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Bridge\DevicesModuleUiModule\Schemas;
use FastyBird\Module\Ui\Hydrators as UiHydrators;

/**
 * Connector property data source entity hydrator
 *
 * @extends UiHydrators\Widgets\DataSources\DataSource<Entities\Widgets\DataSources\ConnectorProperty>
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorProperty extends UiHydrators\Widgets\DataSources\DataSource
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Widgets\DataSources\ConnectorProperty::RELATIONSHIPS_PROPERTY,
	];

	public function getEntityName(): string
	{
		return Entities\Widgets\DataSources\ConnectorProperty::class;
	}

}
