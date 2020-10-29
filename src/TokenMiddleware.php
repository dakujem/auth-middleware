<?php

declare(strict_types=1);

namespace Dakujem\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * This middleware detects tokens in the request and decodes them.
 *
 * By default, it extracts tokens from `Authorization` header or `token` cookie,
 * then decodes them using the provided decoder,
 * and finally writes the decoded tokens to the `token` attribute of the request.
 * All steps are configurable.
 *
 * Uses a set of extractors to extract a token string,
 * a decoder to decode it to a token representation
 * and a writer to write the token to a request attribute.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class TokenMiddleware implements MiddlewareInterface
{
    /** @var callable */
    private $decoder;
    /** @var callable[] */
    private array $extractors;
    /** @var callable */
    private $writer;
    private ?LoggerInterface $logger;

    public function __construct(
        callable $decoder,
        ?iterable $extractors = null,
        ?callable $writer = null,
        ?LoggerInterface $logger = null
    ) {
        $this->decoder = $decoder;
        $this->extractors = $extractors ?? [
                Manipulators::headerExtractor(),
                Manipulators::cookieExtractor(),
            ];
        $this->writer = $writer ?? Manipulators::attributeWriter();
        $this->logger = $logger;
    }

    /**
     * PSR-15 middleware processing.
     *
     * @param Request $request
     * @param Handler $next
     * @return Response
     */
    public function process(Request $request, Handler $next): Response
    {
        return $next->handle(
            $this->writeToken(
                $this->decodeToken(
                    $this->extractToken($request),
                ),
                $request,
            )
        );
    }

    /**
     * Extract a token using extractors.
     *
     * @param Request $request
     * @return string|null
     */
    private function extractToken(Request $request): ?string
    {
        foreach ($this->extractors as $extractor) {
            $token = $extractor($request, $this->logger);
            if (is_string($token) && $token !== '') {
                return $token;
            }
        }

        // log if no extractor found the token
        $this->logger && $this->logger->log(LogLevel::INFO, 'Token not found.');
        return null;
    }

    /**
     * Decode string token to its payload (claims).
     *
     * @param string|null $token
     * @return object|null payload
     */
    private function decodeToken(?string $token): ?object
    {
        return $token !== null ? ($this->decoder)($token, $this->logger) : null;
    }

    /**
     * Process the decoded token.
     *
     * @param object|null $token
     * @param Request $request
     * @return Request
     */
    private function writeToken(?object $token, Request $request): Request
    {
        return ($this->writer)($token, $request, $this->logger);
    }
}
