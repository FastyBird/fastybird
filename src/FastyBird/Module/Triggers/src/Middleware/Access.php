<?php declare(strict_types = 1);

/**
 * AccessMiddleware.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           24.07.20
 */

namespace FastyBird\Module\Triggers\Middleware;

use Contributte\Translation;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\SimpleAuth\Exceptions as SimpleAuthExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Access check middleware
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AccessMiddleware implements MiddlewareInterface
{

	/** @var Translation\Translator */
	private Translation\Translator $translator;

	public function __construct(
		Translation\Translator $translator
	) {
		$this->translator = $translator;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 *
	 * @return ResponseInterface
	 *
	 * @throws JsonApiExceptions\JsonApiErrorException
	 * @throws Translation\Exceptions\InvalidArgument
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);

		} catch (SimpleAuthExceptions\UnauthorizedAccessException $ex) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				$this->translator->translate('//triggers-module.base.messages.unauthorized.heading'),
				$this->translator->translate('//triggers-module.base.messages.unauthorized.message')
			);

		} catch (SimpleAuthExceptions\ForbiddenAccessException $ex) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_FORBIDDEN,
				$this->translator->translate('//triggers-module.base.messages.forbidden.heading'),
				$this->translator->translate('//triggers-module.base.messages.forbidden.message')
			);
		}
	}

}
