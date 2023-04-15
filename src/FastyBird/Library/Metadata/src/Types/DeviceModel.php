<?php declare(strict_types = 1);

/**
 * DeviceModel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           30.11.17
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Devices known models type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceModel extends Consistence\Enum\Enum
{

	/**
	 * Define data types
	 */
	public const MODEL_CUSTOM = 'custom';

	public const MODEL_SONOFF_BASIC = 'sonoff_basic';

	public const MODEL_SONOFF_RF = 'sonoff_rf';

	public const MODEL_SONOFF_TH = 'sonoff_th';

	public const MODEL_SONOFF_SV = 'sonoff_sv';

	public const MODEL_SONOFF_SLAMPHER = 'sonoff_slampher';

	public const MODEL_SONOFF_S20 = 'sonoff_s20';

	public const MODEL_SONOFF_TOUCH = 'sonoff_touch';

	public const MODEL_SONOFF_POW = 'sonoff_pow';

	public const MODEL_SONOFF_POW_R2 = 'sonoff_pow_r2';

	public const MODEL_SONOFF_DUAL = 'sonoff_dual';

	public const MODEL_SONOFF_DUAL_R2 = 'sonoff_dual_r2';

	public const MODEL_SONOFF_4CH = 'sonoff_4ch';

	public const MODEL_SONOFF_4CH_PRO = 'sonoff_4ch_pro';

	public const MODEL_SONOFF_RF_BRIDGE = 'sonoff_rf_bridge';

	public const MODEL_SONOFF_B1 = 'sonoff_b1';

	public const MODEL_SONOFF_LED = 'sonoff_led';

	public const MODEL_SONOFF_T1_1CH = 'sonoff_t1_1ch';

	public const MODEL_SONOFF_T1_2CH = 'sonoff_t1_2ch';

	public const MODEL_SONOFF_T1_3CH = 'sonoff_t1_3ch';

	public const MODEL_SONOFF_S31 = 'sonoff_s31';

	public const MODEL_SONOFF_SC = 'sonoff_sc';

	public const MODEL_SONOFF_SC_PRO = 'sonoff_sc_pro';

	public const MODEL_SONOFF_PS_15 = 'sonoff_ps_15';

	public const MODEL_AI_THINKER_AI_LIGHT = 'ai_thinker_ai_light';

	public const MODEL_FASTYBIRD_WIFI_GW = 'fastybird_wifi_gw';

	public const MODEL_FASTYBIRD_3CH_POWER_STRIP_R1 = 'fastybird_3ch_power_strip_r1';

	public const MODEL_FASTYBIRD_8CH_BUTTONS = '8ch_buttons';

	public const MODEL_FASTYBIRD_16CH_BUTTONS = '16ch_buttons';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
