<?php

namespace Afsdarif\TranslationManager\Resources;

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Afsdarif\TranslationManager\Filters\NotTranslatedFilter;
use Afsdarif\TranslationManager\Pages\QuickTranslate;
use Afsdarif\TranslationManager\Resources\LanguageLineResource\Pages\EditLanguageLine;
use Afsdarif\TranslationManager\Resources\LanguageLineResource\Pages\ListLanguageLines;
use Afsdarif\TranslationManager\Traits\CanRegisterPanelNavigation;
use Spatie\TranslationLoader\LanguageLine;
use Filament\Schemas\Schema;
use BackedEnum;

class LanguageLineResource extends Resource
{
    use CanRegisterPanelNavigation;
    protected static ?string $model = LanguageLine::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $slug = 'translation-manager';

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return static::shouldRegisterOnPanel();
    }

    public static function getLabel(): ?string
    {
        return trans_choice('translation-manager::translations.translation-label', 1);
    }

    public static function getPluralLabel(): ?string
    {
        return trans_choice('translation-manager::translations.translation-label', 2);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('group')
                    ->prefixIcon('heroicon-o-tag')
                    ->disabled(config('translation-manager.disable_key_and_group_editing'))
                    ->label(__('translation-manager::translations.group'))
                    ->required(),

                TextInput::make('key')
                    ->prefixIcon('heroicon-o-key')
                    ->disabled(config('translation-manager.disable_key_and_group_editing'))
                    ->label(__('translation-manager::translations.key'))
                    ->required(),

                ViewField::make('preview')
                    ->view('translation-manager::preview-translation')
                    ->disabled()
                    ->columnSpan(2),

                Section::make(__('translation-manager::translations.translations-header'))->schema([
                    Repeater::make('translations')->schema([
                        Select::make('language')
                            ->prefixIcon('heroicon-o-language')
                            ->label(__('translation-manager::translations.translation-language'))
                            ->options(collect(config('translation-manager.available_locales'))->pluck('name', 'code'))
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->columnSpanFull()
                            ->required(),

                        Textarea::make('text')
                            ->label(__('translation-manager::translations.translation-text'))
                            ->columnSpanFull()
                            ->required(),
                    ])->columns(2)
                        ->addActionLabel(__('translation-manager::translations.add-translation-button'))
                        ->hiddenLabel()
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->grid([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                            'xl' => 3,
                            '2xl' => 4,
                        ])
                        ->columnSpan(2)
                        ->maxItems(count(config('translation-manager.available_locales'))),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getColumns())
            ->filters([NotTranslatedFilter::make()])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25);
    }

    public static function getColumns(): array
    {

        $columns = [
            TextColumn::make('group_and_key')
                ->label(__('translation-manager::translations.group') . ' & ' . __('translation-manager::translations.key'))
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query
                        ->where('group', 'like', "%{$search}%")
                        ->orWhere('key', 'like', "%{$search}%");
                })
                ->getStateUsing(function (Model $record) {
                    return $record->group . '.' . $record->key;
                }),

            ViewColumn::make('preview')
                ->view('translation-manager::preview-column')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query
                        ->where('text', 'like', "%{$search}%");
                })
                ->label(__('translation-manager::translations.preview-in-your-lang', ['lang' => app()->getLocale()]))
                ->sortable(false),
        ];

        foreach (config('translation-manager.available_locales') as $locale) {
            $localeCode = $locale['code'];

            $columns[] = IconColumn::make($localeCode)
                ->label(strtoupper($localeCode))
                ->searchable(false)
                ->sortable(false)
                ->getStateUsing(function (LanguageLine $record) use ($localeCode) {
                    return in_array($localeCode, array_keys($record->text));
                })
                ->boolean();
        }

        return $columns;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguageLines::route('/'),
            'edit' => EditLanguageLine::route('/{record}/edit'),
            'quick-translate' => QuickTranslate::route('/quick-translate'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterOnPanel() ? Gate::allows('use-translation-manager') : false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::shouldRegisterOnPanel() ? Gate::allows('use-translation-manager') : false;
    }

    public static function getNavigationLabel(): string
    {
        return __('translation-manager::translations.translation-navigation-label');
    }

    public static function getNavigationIcon(): ?string
    {
        if (config('translation-manager.navigation_icon') === false) {
            return null;
        }

        return config('translation-manager.navigation_icon', static::$navigationIcon);
    }

    public static function getNavigationGroup(): ?string
    {
        if (config('translation-manager.navigation_group_translation_key')) {
            return __(config('translation-manager.navigation_group_translation_key'));
        }

        return config('translation-manager.navigation_group');
    }

    public static function getCluster(): ?string
    {
        return config('translation-manager.cluster');
    }
}
