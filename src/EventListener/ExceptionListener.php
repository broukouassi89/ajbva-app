<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ExceptionListener
{
    public function __construct(
        private readonly Environment $twig,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // En dev, laisser Symfony afficher ses pages d'erreur détaillées
        if ($this->appEnv === 'dev') {
            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $content = $this->twig->render('error/404.html.twig', [
                'message' => 'La page demandée n\'existe pas.',
            ]);
            $event->setResponse(new Response($content, 404));
            return;
        }

        if ($exception instanceof AccessDeniedHttpException) {
            $content = $this->twig->render('error/403.html.twig', [
                'message' => 'Vous n\'avez pas les droits pour accéder à cette ressource.',
            ]);
            $event->setResponse(new Response($content, 403));
        }
    }
}
