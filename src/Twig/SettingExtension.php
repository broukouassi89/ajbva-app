<?php

namespace App\Twig;

use App\Service\SettingService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingExtension extends AbstractExtension
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_setting', [$this->settingService, 'get']),
        ];
    }
}
