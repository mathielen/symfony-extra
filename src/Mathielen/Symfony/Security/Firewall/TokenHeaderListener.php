<?php
namespace Mathielen\Symfony\Security\Firewall;

use Infrastructure\Exception\UnauthorizedException;
use Mathielen\Symfony\Security\SessionValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class TokenHeaderListener implements ListenerInterface
{

    const HEADER = 'x-auth-token';

    /**
     * @var SessionValidatorInterface
     */
    protected $sessionValidator;

    public function __construct(SessionValidatorInterface $sessionValidator)
    {
        $this->sessionValidator = $sessionValidator;
    }

    public static function isAnonymous(Request $request)
    {
        return
            !$request->headers->has(self::HEADER) &&
            !$request->query->has(self::HEADER);
    }

    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (self::isAnonymous($request)) {
            return;
        }

        if ($request->headers->has(self::HEADER)) {
            $sessionId = $request->headers->get(self::HEADER);
        } elseif ($request->query->has(self::HEADER)) {
            $sessionId = $request->query->get(self::HEADER);
        }

        if (empty($sessionId)) {
            return;
        }
        if ($this->sessionValidator->validate($sessionId)) {
            return;
        }

        // By default deny authorization
        $response = new Response(null, 401); //401, not 403
        $event->setResponse($response);
    }
}
