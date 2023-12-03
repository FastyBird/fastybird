<?php declare(strict_types = 1);

/**
 * HsbTransformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           12.04.23
 */

namespace FastyBird\Library\Metadata\ValueObjects;

use function abs;
use function floor;
use function intval;
use function min;
use function round;

/**
 * HSB value object
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class HsbTransformer implements Transformer
{

	public function __construct(
		private readonly float $hue,
		private readonly float $saturation,
		private readonly float $brightness,
	)
	{
	}

	public function getHue(): float
	{
		return $this->hue;
	}

	public function getSaturation(): float
	{
		return $this->saturation;
	}

	public function getBrightness(): float
	{
		return $this->brightness;
	}

	public function toRgb(): RgbTransformer
	{
		$hue = $this->hue;
		$saturation = $this->saturation;
		$brightness = $this->brightness;

		if ($hue < 0) {
			$hue = 0;
		}

		if ($hue > 360) {
			$hue = 360;
		}

		if ($saturation < 0) {
			$saturation = 0;
		}

		if ($saturation > 100) {
			$saturation = 100;
		}

		if ($brightness < 0) {
			$brightness = 0;
		}

		if ($brightness > 100) {
			$brightness = 100;
		}

		$dS = $saturation / 100.0;
		$dV = $brightness / 100.0;
		$dC = $dV * $dS;
		$dH = $hue / 60.0;
		$dT = $dH;

		while ($dT >= 2.0) {
			$dT -= 2.0;
		}

		$dX = $dC * (1 - abs($dT - 1));

		switch (intval(floor($dH))) {
			case 0:
				$dR = $dC;
				$dG = $dX;
				$dB = 0.0;

				break;
			case 1:
				$dR = $dX;
				$dG = $dC;
				$dB = 0.0;

				break;
			case 2:
				$dR = 0.0;
				$dG = $dC;
				$dB = $dX;

				break;
			case 3:
				$dR = 0.0;
				$dG = $dX;
				$dB = $dC;

				break;
			case 4:
				$dR = $dX;
				$dG = 0.0;
				$dB = $dC;

				break;
			case 5:
				$dR = $dC;
				$dG = 0.0;
				$dB = $dX;

				break;
			default:
				$dR = 0.0;
				$dG = 0.0;
				$dB = 0.0;

				break;
		}

		$dM = $dV - $dC;
		$dR += $dM;
		$dG += $dM;
		$dB += $dM;
		$dR *= 255;
		$dG *= 255;
		$dB *= 255;
		$dR = intval(round($dR));
		$dG = intval(round($dG));
		$dB = intval(round($dB));

		$dW = intval(round(min($dR, $dG, $dB) * $brightness / 100));

		return new RgbTransformer($dR, $dG, $dB, $dW);
	}

	public function toArray(): array
	{
		return [
			'hue' => $this->getHue(),
			'saturation' => $this->getSaturation(),
			'brightness' => $this->getBrightness(),
		];
	}

}
