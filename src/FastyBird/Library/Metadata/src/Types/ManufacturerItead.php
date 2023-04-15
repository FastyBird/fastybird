<?php declare(strict_types = 1);

/**
 * ManufacturerItead.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           06.07.18
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * ITEAD manufacturer models types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ManufacturerItead extends Consistence\Enum\Enum
{

	/**
	 * Define itead models
	 */
	public const MODEL_SONOFF_BASIC = DeviceModel::MODEL_SONOFF_BASIC;

	public const MODEL_SONOFF_RF = DeviceModel::MODEL_SONOFF_RF;

	public const MODEL_SONOFF_TH = DeviceModel::MODEL_SONOFF_TH;

	public const MODEL_SONOFF_SV = DeviceModel::MODEL_SONOFF_SV;

	public const MODEL_SONOFF_SLAMPHER = DeviceModel::MODEL_SONOFF_SLAMPHER;

	public const MODEL_SONOFF_S20 = DeviceModel::MODEL_SONOFF_S20;

	public const MODEL_SONOFF_TOUCH = DeviceModel::MODEL_SONOFF_TOUCH;

	public const MODEL_SONOFF_POW = DeviceModel::MODEL_SONOFF_POW;

	public const MODEL_SONOFF_POW_R2 = DeviceModel::MODEL_SONOFF_POW_R2;

	public const MODEL_SONOFF_DUAL = DeviceModel::MODEL_SONOFF_DUAL;

	public const MODEL_SONOFF_DUAL_R2 = DeviceModel::MODEL_SONOFF_DUAL_R2;

	public const MODEL_SONOFF_4CH = DeviceModel::MODEL_SONOFF_4CH;

	public const MODEL_SONOFF_4CH_PRO = DeviceModel::MODEL_SONOFF_4CH_PRO;

	public const MODEL_SONOFF_RF_BRIDGE = DeviceModel::MODEL_SONOFF_RF_BRIDGE;

	public const MODEL_SONOFF_B1 = DeviceModel::MODEL_SONOFF_B1;

	public const MODEL_SONOFF_LED = DeviceModel::MODEL_SONOFF_LED;

	public const MODEL_SONOFF_T1_1CH = DeviceModel::MODEL_SONOFF_T1_1CH;

	public const MODEL_SONOFF_T1_2CH = DeviceModel::MODEL_SONOFF_T1_2CH;

	public const MODEL_SONOFF_T1_3CH = DeviceModel::MODEL_SONOFF_T1_3CH;

	public const MODEL_SONOFF_S31 = DeviceModel::MODEL_SONOFF_S31;

	public const MODEL_SONOFF_SC = DeviceModel::MODEL_SONOFF_SC;

	public const MODEL_SONOFF_SC_PRO = DeviceModel::MODEL_SONOFF_SC_PRO;

	public const MODEL_SONOFF_PS_15 = DeviceModel::MODEL_SONOFF_PS_15;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
