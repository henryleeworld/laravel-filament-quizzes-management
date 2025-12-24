<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Difficulty: string implements HasColor, HasLabel
{
    case Easy = 'easy';
    case Hard = 'hard';
    case Medium = 'medium';

    public function getLabel(): string
    {
        return match ($this) {
            self::Easy => __('Easy'),
            self::Hard => __('Hard'),
            self::Medium => __('Medium'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Easy => 'success',
            self::Hard => 'danger',
            self::Medium => 'warning',
        };
    }
}
