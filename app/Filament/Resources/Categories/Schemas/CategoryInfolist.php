<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Overview')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug'),
                        TextEntry::make('icon')
                            ->placeholder('-'),
                        TextEntry::make('sort_order')
                            ->numeric(),
                    ]),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->size('xs')
                    ->color('gray'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->size('xs')
                    ->color('gray'),
            ]);
    }
}
