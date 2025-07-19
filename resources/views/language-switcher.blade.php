@php
    use Illuminate\Support\Str;

    if (!function_exists('try_svg')) {
        function try_svg($name, $classes = '', array $attributes = []) {
            try {
                return svg($name, $classes, $attributes)->toHtml();
            } catch (\Exception $e) {
                return '❓';
            }
        }
    }

    $hasFlags = $showFlags ?? true;
@endphp

<x-filament::dropdown
    id="filament-language-switcher"
    class="fi-user-menu"
    placement="bottom-end"
    teleport
>
    <x-slot name="trigger">
        <button
            type="button"
            class="fi-user-menu-trigger"
            aria-label="Open language menu"
        >
            <div class="flex items-center justify-center fi-avatar fi-circular fi-size-md bg-gray-200 dark:bg-gray-900">
                @if (isset($currentLanguage) && $hasFlags)
                    {!! try_svg('flag-1x1-' . $currentLanguage['flag'], 'fi-avatar fi-circular fi-size-md') !!}
                @else
                    <x-icon name="heroicon-o-language" class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                @endif
            </div>
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        @foreach ($otherLanguages as $language)
            @php
                $isCurrent = isset($currentLanguage) && $currentLanguage['code'] === $language['code'];
            @endphp
            
            <x-filament::dropdown.list.item
                :tag="!$isCurrent ? 'a' : 'div'"
                :href="!$isCurrent ? route('translation-manager.switch', ['code' => $language['code']]) : null"
                :color="$isCurrent ? 'gray' : null"
                :icon="null"
                :disabled="$isCurrent"
            >
                <span class="flex items-center gap-2 w-full">
                    @if ($hasFlags)
                        {!! try_svg('flag-4x3-' . $language['flag'], 'w-5 h-5', ['style' => 'display:inline-block !important; margin-right:10px;']) !!}
                    @endif

                    <span @class(['font-semibold' => $isCurrent])>
                        {{ Str::upper($language['code']) }} - {{ $language['name'] }}
                    </span>
                </span>
            </x-filament::dropdown.list.item>

        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
