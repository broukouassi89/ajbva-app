<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
class LocaleListener
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Forcer la locale française pour toutes les requêtes
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Priorité : paramètre de route > session > défaut
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            $request->setLocale($request->getSession()->get('_locale', 'fr'));
        }
    }
}
