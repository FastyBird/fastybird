<?php declare(strict_types = 1);

/**
 * HsbTransformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ToolsLibrary!
 * @subpackage     Transformers
 * @since          1.0.0
 *
 * @date           12.04.23
 */

namespace FastyBird\Library\Tools\Transformers;

/**
 * HSB value object
 *
 * @package        FastyBird:Transformers!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class HsbTransformer implements Transformer
{

	public function __construct(
		private float $hue,
		private float $saturation,
		private float $brightness,
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

	public function toMired(): MiredTransformer
	{
		$rgb = $this->toRgb();

		// This is a basic example and may not provide accurate results for all scenarios
		$temperature = 0;

		// Calculate color temperature based on RGB values
		$X = (-0.14282 * $rgb->getRed()) + (1.54924 * $rgb->getGreen()) + (-0.95641 * $rgb->getBlue());
		$Y = (-0.32466 * $rgb->getRed()) + (1.57837 * $rgb->getGreen()) + (-0.73191 * $rgb->getBlue());
		$Z = (-0.68202 * $rgb->getRed()) + (0.77073 * $rgb->getGreen()) + (0.56332 * $rgb->getBlue());

		// Calculate xy values
		$x = $X / ($X + $Y + $Z);
		$y = $Y / ($X + $Y + $Z);

		// Calculate correlated color temperature (CCT)
		$n = ( $x - 0.3320 ) / ( 0.1858 - $y );

		$temperature = 449.0 * pow($n, 3) + 3525.0 * pow($n, 2) + 6823.3 * $n + 5520.33;

		return new MiredTransformer(intval(round(1_000_000 / $temperature)), $this->getBrightness());
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
