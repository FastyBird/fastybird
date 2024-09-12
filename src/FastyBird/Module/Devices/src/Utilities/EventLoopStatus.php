<?php declare(strict_types = 1);

/**
 * EventLoopStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           12.09.24
 */

namespace FastyBird\Module\Devices\Utilities;

use Nette;

/**
 * Event loop status helper
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EventLoopStatus
{

	use Nette\SmartObject;

	private bool $status = false;

	public function setStatus(bool $status): void
	{
		$this->status = $status;
	}

	public function isRunning(): bool
	{
		return $this->status;
	}

}
