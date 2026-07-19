<?php

declare(strict_types=1);

namespace App\MoonShine\Palettes;

use MoonShine\Contracts\ColorManager\PaletteContract;

final class LppPalette implements PaletteContract
{
    public function getDescription(): string
    {
        return 'LPP blue';
    }

    public function getColors(): array
    {
        return [
            'body' => '#f8fafc',
            'primary' => '#2563eb',
            'primary-text' => '#ffffff',
            'secondary' => '#0f172a',
            'secondary-text' => '#ffffff',
            'base' => [
                'text' => '#0f172a',
                'stroke' => '#dbe4ee',
                'default' => '#ffffff',
                50 => '#f8fafc',
                100 => '#f1f5f9',
                200 => '#e2e8f0',
                300 => '#cbd5e1',
                400 => '#94a3b8',
                500 => '#64748b',
                600 => '#475569',
                700 => '#334155',
                800 => '#1e293b',
                900 => '#0f172a',
            ],
            'success' => '#10b981',
            'success-text' => '#047857',
            'warning' => '#f59e0b',
            'warning-text' => '#92400e',
            'error' => '#ef4444',
            'error-text' => '#b91c1c',
            'info' => '#06b6d4',
            'info-text' => '#0e7490',
        ];
    }

    public function getDarkColors(): array
    {
        return [
            'body' => '#0f172a',
            'primary' => '#60a5fa',
            'primary-text' => '#0f172a',
            'secondary' => '#1e293b',
            'secondary-text' => '#f8fafc',
            'base' => [
                'text' => '#e2e8f0',
                'stroke' => '#334155',
                'default' => '#111827',
                50 => '#172033',
                100 => '#1e293b',
                200 => '#273449',
                300 => '#334155',
                400 => '#475569',
                500 => '#64748b',
                600 => '#94a3b8',
                700 => '#cbd5e1',
                800 => '#e2e8f0',
                900 => '#f8fafc',
            ],
            'success' => '#34d399',
            'success-text' => '#a7f3d0',
            'warning' => '#fbbf24',
            'warning-text' => '#fde68a',
            'error' => '#f87171',
            'error-text' => '#fecaca',
            'info' => '#22d3ee',
            'info-text' => '#a5f3fc',
        ];
    }
}
