<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Settings & Administration (সিস্টেম ও সেটিংস)';

    protected static ?int $navigationSort = 1;

    /**
     * Only Admin can view and manage user accounts.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('Users & Roles (ব্যবহারকারী ও অনুমতি)');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('User Credentials & Role Assignment'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Full Name (ব্যবহারকারীর নাম)'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('Email Address (ইমেইল)'))
                            ->email()
                            ->required()
                            ->rules(['regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i'])
                            ->validationMessages([
                                'regex' => 'Only Google Gmail (@gmail.com) addresses are allowed.',
                            ])
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label(__('Password (পাসওয়ার্ড)'))
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('Leave blank to keep current password.') : null),

                        Forms\Components\Select::make('roles')
                            ->label(__('User Role (অ্যাক্সেস রোল)'))
                            ->relationship('roles', 'name')
                            ->preload()
                            ->required()
                            ->helperText(__('Admin: Full access to all modules and settings. Viewer: Read-only access.')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name (নাম)'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email (ইমেইল)'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('Assigned Role (রোল)'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'success',
                        'Viewer' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label(__('Status (ভেরিফিকেশন)'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (User $record): string => $record->hasVerifiedEmail() ? __('Email Verified') : __('Pending Verification')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Registered (নিবন্ধন তারিখ)'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('verifyNow')
                    ->label(__('Verify (ভেরিফাই)'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
                    ->requiresConfirmation()
                    ->modalHeading(__('Verify User Email'))
                    ->modalDescription(fn (User $record) => __("Are you sure you want to manually verify the email for {$record->name} ({$record->email})?"))
                    ->action(function (User $record) {
                        $record->markEmailAsVerified();
                        \Filament\Notifications\Notification::make()
                            ->title(__('ইমেইল সফলভাবে ভেরিফাই করা হয়েছে'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('resendVerification')
                    ->label(__('Resend Link (লিঙ্ক পাঠান)'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
                    ->action(function (User $record) {
                        try {
                            $notification = app(\App\Notifications\Auth\VerifyEmail::class);
                            $notification->url = \Filament\Facades\Filament::getVerifyEmailUrl($record);
                            $record->notifyNow($notification);

                            \Filament\Notifications\Notification::make()
                                ->title(__('ভেরিফিকেশন ইমেইল সফলভাবে পাঠানো হয়েছে'))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('ইমেইল পাঠানো ব্যর্থ হয়েছে: ') . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->id === auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
