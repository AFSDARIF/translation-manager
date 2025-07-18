<?php

namespace Afsdarif\TranslationManager;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\View\View;
use Afsdarif\TranslationManager\Http\Middleware\SetLanguage;
use Afsdarif\TranslationManager\Pages\QuickTranslate;
use Afsdarif\TranslationManager\Resources\LanguageLineResource;

class TranslationManagerPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'translation-manager';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                LanguageLineResource::class,
            ])
            ->pages([
                QuickTranslate::class,
            ]);

        if (config('translation-manager.language_switcher')) {
            $panel->renderHook(
                config('translation-manager.language_switcher_render_hook'),
                fn (): View => $this->getLanguageSwitcherView()
            );

            $panel->authMiddleware([
                SetLanguage::class,
            ]);
        }

    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Returns a View object that renders the language switcher component.
     *
     * @return \Illuminate\Contracts\View\View The View object that renders the language switcher component.
     */
    private function getLanguageSwitcherView(): View
    {
        $locales = config('translation-manager.available_locales');
        $currentLocale = app()->getLocale();
        $currentLanguage = collect($locales)->firstWhere('code', $currentLocale);
        $otherLanguages = $locales;
        $showFlags = config('translation-manager.show_flags');

        return view('translation-manager::language-switcher', compact(
            'otherLanguages',
            'currentLanguage',
            'showFlags',
        ));
    }
}
