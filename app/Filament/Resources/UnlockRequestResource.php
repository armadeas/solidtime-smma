<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UnlockRequestStatus;
use App\Filament\Resources\UnlockRequestResource\Pages;
use App\Models\UnlockRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UnlockRequestResource extends Resource
{
    protected static ?string $model = UnlockRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-open';

    protected static ?string $navigationGroup = 'Timetracking';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Unlock Requests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->relationship('organization', 'name')
                            ->required()
                            ->default(function () {
                                $user = auth()->user();
                                return $user && $user->currentTeam ? $user->currentTeam->id : null;
                            })
                            ->disabled(fn ($record) => $record !== null),
                        Forms\Components\Select::make('requester_member_id')
                            ->label('Requester')
                            ->options(function () {
                                $user = auth()->user();
                                if (!$user || !$user->currentTeam) {
                                    return [];
                                }

                                $organization = $user->currentTeam;
                                return $organization->members()
                                    ->with('user')
                                    ->get()
                                    ->mapWithKeys(function ($member) {
                                        return [$member->id => $member->user->name ?? 'Unknown'];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(function () {
                                $user = auth()->user();
                                if (!$user || !$user->currentTeam) {
                                    return null;
                                }

                                return $user->currentTeam->members()
                                    ->where('user_id', $user->id)
                                    ->first()?->id;
                            })
                            ->visible(fn ($record) => $record === null)
                            ->disabled(function () {
                                $user = auth()->user();
                                if (!$user || !$user->currentTeam) {
                                    return true;
                                }

                                $member = $user->currentTeam->members()
                                    ->where('user_id', $user->id)
                                    ->first();

                                return !$member || !in_array($member->role, ['owner', 'admin']);
                            })
                            ->helperText(function () {
                                $user = auth()->user();
                                if (!$user || !$user->currentTeam) {
                                    return null;
                                }

                                $member = $user->currentTeam->members()
                                    ->where('user_id', $user->id)
                                    ->first();

                                return $member && in_array($member->role, ['owner', 'admin'])
                                    ? 'As an admin, you can create unlock requests on behalf of other users.'
                                    : null;
                            }),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Unlock Request')
                            ->rows(3)
                            ->maxLength(65535)
                            ->disabled(fn ($record) => $record !== null)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'expired' => 'Expired',
                            ])
                            ->default('pending')
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('approver_member_id')
                            ->relationship('approver.user', 'name')
                            ->disabled()
                            ->visible(fn ($record) => $record?->approver_member_id !== null),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->disabled()
                            ->visible(fn ($record) => $record?->approved_at !== null),
                        Forms\Components\DateTimePicker::make('rejected_at')
                            ->disabled()
                            ->visible(fn ($record) => $record?->rejected_at !== null),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->disabled()
                            ->visible(fn ($record) => $record?->expires_at !== null),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('requester.user.name')
                    ->label('Requester')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\UnlockRequestStatus ? $state->value : $state)
                    ->color(fn ($state): string => match ($state instanceof \App\Enums\UnlockRequestStatus ? $state->value : $state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (UnlockRequest $record) => $record->status->value === 'pending')
                    ->action(function (UnlockRequest $record) {
                        $member = $record->organization->members()
                            ->where('user_id', auth()->id())
                            ->first();

                        if ($member) {
                            $record->approve($member);
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Unlock request approved')
                                ->body('The unlock request has been approved and will expire in 30 minutes.')
                                ->send();
                        }
                    })
                    ->authorize('update'),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (UnlockRequest $record) => $record->status->value === 'pending')
                    ->action(function (UnlockRequest $record) {
                        $member = $record->organization->members()
                            ->where('user_id', auth()->id())
                            ->first();

                        if ($member) {
                            $record->reject($member);
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Unlock request rejected')
                                ->send();
                        }
                    })
                    ->authorize('update'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (UnlockRequest $record) => $record->status->value === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnlockRequests::route('/'),
            'create' => Pages\CreateUnlockRequest::route('/create'),
            'view' => Pages\ViewUnlockRequest::route('/{record}'),
            'edit' => Pages\EditUnlockRequest::route('/{record}/edit'),
        ];
    }
}

