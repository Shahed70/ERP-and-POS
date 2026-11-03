<?php

namespace App\Filament\Pages;

use App\Models\ShopSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteLogoHeader')
                ->label(__('Delete Current Logo (লোগো মুছুন)'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn (): bool => filled(ShopSetting::getSettings()->logo_path))
                ->requiresConfirmation()
                ->modalHeading(__('Delete Shop Logo? (দোকানের লোগো মুছে ফেলবেন?)'))
                ->modalDescription(__('Are you sure you want to remove the current shop logo? The system default store icon will be displayed instead.'))
                ->action(fn () => $this->deleteLogo()),
        ];
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
                    ->description(__('Configure shop name, slogan, brand logo, and business identity displayed on invoices, receipts, and reports.'))
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

                        Forms\Components\ViewField::make('current_logo_preview')
                            ->view('filament.components.current-logo-preview')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('logo_path')
                            ->label(__('Upload New Logo / Replace Logo (নতুন লোগো আপলোড বা পরিবর্তন)'))
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('shop-logos')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->deletable()
                            ->columnSpanFull()
                            ->helperText(__('Select a PNG, JPG, WEBP, or SVG image file (max 5MB). Once chosen, click "Save Settings" below to apply the new logo beside the shop name.')),
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

    public function deleteLogo(): void
    {
        $settings = ShopSetting::getSettings();
        if ($settings->logo_path) {
            if (Storage::disk('public')->exists($settings->logo_path)) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $settings->update(['logo_path' => null]);
        }

        $this->data['logo_path'] = null;
        $this->form->fill($settings->fresh()->toArray());

        Notification::make()
            ->title(__('লোগো সফলভাবে মুছে ফেলা হয়েছে / Logo deleted successfully!'))
            ->success()
            ->send();
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settings = ShopSetting::getSettings();

        // If replacing an existing logo with a new one, remove the old file from storage
        if (isset($state['logo_path']) && $state['logo_path'] !== $settings->logo_path) {
            if ($settings->logo_path && Storage::disk('public')->exists($settings->logo_path)) {
                Storage::disk('public')->delete($settings->logo_path);
            }
        }

        // If logo was cleared in form
        if (empty($state['logo_path']) && $settings->logo_path) {
            if (Storage::disk('public')->exists($settings->logo_path)) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $state['logo_path'] = null;
        }

        $settings->update($state);
        $this->form->fill($settings->fresh()->toArray());

        Notification::make()
            ->title(__('Shop Settings Saved Successfully! (দোকান সেটিংস সফলভাবে সংরক্ষিত হয়েছে)'))
            ->success()
            ->send();
    }
}
