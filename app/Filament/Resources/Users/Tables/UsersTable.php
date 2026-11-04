<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Plan;
use App\Filament\Actions\AjusterQuota;
use App\Models\User;
use App\Services\Administration\ConsommationUtilisateur;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('administration.utilisateurs.nom'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('administration.utilisateurs.email'))
                    ->searchable(),
                TextColumn::make('plan')
                    ->label(__('administration.utilisateurs.plan'))
                    ->badge()
                    ->formatStateUsing(fn (Plan $state): string => (string) config("voxia.plans.{$state->value}.label", $state->name)),
                TextColumn::make('created_at')
                    ->label(__('administration.utilisateurs.inscription'))
                    ->date()
                    ->sortable(),
                TextColumn::make('consommation')
                    ->label(__('administration.utilisateurs.consommation'))
                    ->state(fn (User $record): array => app(ConsommationUtilisateur::class)->lignes($record))
                    ->listWithLineBreaks(),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->label(__('administration.utilisateurs.filtre_plan'))
                    ->options(collect(Plan::cases())->mapWithKeys(
                        fn (Plan $plan) => [$plan->value => (string) config("voxia.plans.{$plan->value}.label", $plan->name)],
                    )->all()),
            ])
            ->recordActions([
                AjusterQuota::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }
}
