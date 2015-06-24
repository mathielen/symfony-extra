<?php
namespace Mathielen\Symfony\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Mathielen\Symfony\Domain\Security\SessionManager;

class TokenHeaderListener implements ListenerInterface
{

    const HEADER = 'x-auth-token';

    /**
     * @var SessionManager
     */
    protected $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
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
        if ($this->sessionManager->validateAndExtendTimeout($sessionId)) {
            return;
        }

        // By default deny authorization
        $response = new Response(null, 403);
        $event->setResponse($response);
    }
}
