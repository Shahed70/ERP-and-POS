<?php

namespace App\Filament\Pages;

use App\Models\ShopSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ShopSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings & Administration (সিস্টেম ও সেটিংস)';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.shop-settings-page';

    public ?array $data = [];

    /**
     * Only Admin can access Shop Settings.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('Shop Settings & Branding (দোকান সেটিংস)');
    }

    public function getTitle(): string
    {
        return __('Shop Settings, Logo & Brand Customization');
    }

    public function mount(): void
    {
        $settings = ShopSetting::getSettings();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Shop Identity & Branding (দোকানের নাম ও পরিচিতি)'))
                    ->description(__('Configure shop name, slogan and business identity displayed on invoices, receipts, and reports.'))
                    ->schema([
                        Forms\Components\TextInput::make('shop_name')
                            ->label(__('Shop Name (দোকানের নাম)'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('shop_tagline')
                            ->label(__('Slogan / Tagline (স্লোগান / সাবটাইটেল)'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('proprietor_name')
                            ->label(__('Proprietor / Contact Person (প্রোপ্রাইটর)'))
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('logo_path')
                            ->label(__('Shop Brand Logo (লোগো আপলোড)'))
                            ->image()
                            ->disk('public')
                            ->directory('shop-logos')
                            ->visibility('public')
                            ->helperText(__('PNG or JPG. Displayed on top navigation, sales invoices, money receipts, and reports.')),
                    ])->columns(2),

                Forms\Components\Section::make(__('Contact & Location (যোগাযোগ ও ঠিকানা)'))
                    ->description(__('Official phone and godown address printed on invoices and payment receipts.'))
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone / Hotline (মোবাইল নম্বর)'))
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('Official Email (ইমেইল)'))
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label(__('Full Address / Location (দোকান ও গুদামের ঠিকানা)'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('Invoice & Financial Defaults (চালান ও রসিদ সেটিংস)'))
                    ->schema([
                        Forms\Components\TextInput::make('currency_symbol')
                            ->label(__('Currency Symbol (মুদ্রা প্রতীক)'))
                            ->default('৳')
                            ->required()
                            ->maxLength(10),

                        Forms\Components\Textarea::make('invoice_footer_note')
                            ->label(__('Invoice & Receipt Footer Note (চালান ও রসিদের শেষের বার্তা)'))
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText(__('Custom terms, warranty/return conditions, or thank you message printed on customer receipts.')),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settings = ShopSetting::getSettings();
        $settings->update($state);

        Notification::make()
            ->title(__('Shop Settings Saved Successfully! (দোকান সেটিংস সফলভাবে সংরক্ষিত হয়েছে)'))
            ->success()
            ->send();
    }
}
