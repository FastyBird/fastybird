<?php declare(strict_types = 1);

/**
 * EquationFormat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           26.04.23
 */

namespace FastyBird\Library\Metadata\ValueObjects;

use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions;
use MathSolver\Math;
use Nette;
use function array_key_exists;
use function implode;
use function is_array;
use function preg_match;

/**
 * Equation value format
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EquationFormat
{

	use Nette\SmartObject;

	private string $equationFrom;

	private string|null $equationTo = null;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(string $equation)
	{
		if (
			preg_match(Metadata\Constants::VALUE_FORMAT_EQUATION, $equation, $matches) === 1
			&& array_key_exists('equation_x', $matches)
		) {
			$this->equationFrom = $matches['equation_x'];

			if (array_key_exists('equation_y', $matches)) {
				$this->equationTo = $matches['equation_y'];
			}
		} else {
			throw new Exceptions\InvalidArgument('Provided equation format is not valid');
		}
	}

	public function getEquationFrom(): Math
	{
		return Math::from($this->equationFrom);
	}

	public function getEquationTo(): Math|null
	{
		return $this->equationTo !== null ? Math::from($this->equationTo) : null;
	}

	public function getValue(): string
	{
		return $this->__toString();
	}

	public function __toString(): string
	{
		$from = $this->getEquationFrom()->string();
		$to = $this->getEquationTo()?->string();

		return 'equation:x='
			. (is_array($from) ? implode($from) : $from)
			. ($to !== null ? ':y=' . (is_array($to) ? implode($to) : $to) : '');
	}

}
