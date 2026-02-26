<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest')]
class PasswordChangeCheckListener
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly Security $security
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Ne pas rediriger pour les routes de sécurité, de changement de mot de passe ou les assets
        if (in_array($route, ['security_change_password', 'security_logout', 'security_login', null]) || str_starts_with($route, '_')) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if ($user && $user->isMustChangePassword()) {
            $event->setResponse(new RedirectResponse($this->router->generate('security_change_password')));
        }
    }
}
