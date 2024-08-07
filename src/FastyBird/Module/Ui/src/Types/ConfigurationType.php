<?php declare(strict_types = 1);

/**
 * ConfigurationType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Types;

/**
 * Configuration cache types
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConfigurationType: string
{

	case DASHBOARDS = 'dashboards';

	case DASHBOARDS_TABS = 'dashboards_tabs';

	case GROUPS = 'groups';

	case WIDGETS = 'widgets';

	case WIDGETS_DATA_SOURCES = 'widgets_data_sources';

	case WIDGETS_DISPLAY = 'widgets_display';

}
