<?php declare(strict_types = 1);

/**
 * ActionCommandReceived.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           09.08.24
 */

namespace FastyBird\Module\Ui\Events;

use FastyBird\Module\Ui\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Socket action command received
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ActionCommandReceived extends EventDispatcher\Event
{

	public function __construct(
		private readonly Documents\Widgets\DataSources\Actions\Action $action,
		private readonly Documents\Widgets\DataSources\DataSource $dataSource,
	)
	{
	}

	public function getAction(): Documents\Widgets\DataSources\Actions\Action
	{
		return $this->action;
	}

	public function getDataSource(): Documents\Widgets\DataSources\DataSource
	{
		return $this->dataSource;
	}

}
