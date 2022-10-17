<?php declare(strict_types = 1);

/**
 * Access.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           24.07.20
 */

namespace FastyBird\DevicesModule\Middleware;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\SimpleAuth\Exceptions as SimpleAuthExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Access check middleware
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Access implements MiddlewareInterface
{

	public function __construct(private readonly Localization\Translator $translator)
	{
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		} catch (SimpleAuthExceptions\UnauthorizedAccess) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				$this->translator->translate('//devices-module.base.messages.unauthorized.heading'),
				$this->translator->translate('//devices-module.base.messages.unauthorized.message'),
			);
		} catch (SimpleAuthExceptions\ForbiddenAccess) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_FORBIDDEN,
				$this->translator->translate('//devices-module.base.messages.forbidden.heading'),
				$this->translator->translate('//devices-module.base.messages.forbidden.message'),
			);
		}
	}

}
