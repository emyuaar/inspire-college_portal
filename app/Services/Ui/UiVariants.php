<?php

namespace App\Services\Ui;

final class UiVariants
{
    /**
     * Normalize a variant string to a known set.
     */
    public static function normalize(?string $variant): string
    {
        $v = strtolower(trim((string) $variant));

        return match ($v) {
            'success', 'warning', 'error', 'info', 'neutral', 'brand' => $v,
            // common synonyms
            'danger' => 'error',
            default => 'neutral',
        };
    }

    /**
     * Badge classes used by <x-ui.badge />.
     */
    public static function badge(string $variant): string
    {
        $v = self::normalize($variant);

        $map = [
            'success' => 'bg-green-100 text-green-700 border border-green-200',
            'warning' => 'bg-amber-50 text-amber-700 border border-amber-200',
            'error' => 'bg-red-50 text-red-700 border border-red-200',
            'info' => 'bg-blue-50 text-blue-700 border border-blue-200',
            'neutral' => 'bg-slate-100 text-slate-700 border border-slate-200',
            'brand' => 'bg-ds-navy/10 text-ds-navy border border-ds-navy/20',
        ];

        return $map[$v] ?? $map['neutral'];
    }

    /**
     * A subtle theme intended for learner-facing assignment cards.
     * Use `success`/`warning`/`error` for final outcomes; anything else falls back to `neutral`.
     *
     * @return array{card:string,accentBorder:string,header:string,icon:string,accentText:string,accentHoverText:string,resultStrip:string,resultIconBg:string,resultIcon:string,resultKicker:string,resultTitle:string,resultBodyText:string,panel:string,panelBody:string,summaryIcon:string,submissionBox:string,uploadFileButton:string,uploadFileHoverButton:string,uploadInputBorderHover:string}
     */
    public static function assignmentCard(string $variant): array
    {
        $v = self::normalize($variant);
        if (!in_array($v, ['success', 'warning', 'error'], true)) {
            $v = 'neutral';
        }

        $map = [
            'success' => [
                'card' => 'border-green-200/70 bg-green-50/20',
                'accentBorder' => 'border-t-4 border-t-green-500/60',
                'header' => 'bg-green-50/60 border-green-100',
                'icon' => 'bg-green-600/10 text-green-700',
                'accentText' => 'text-green-700',
                'accentHoverText' => 'group-hover:text-green-700',
                'resultStrip' => 'bg-gradient-to-r from-green-50 to-white border-b border-green-200/50',
                'resultIconBg' => 'bg-green-600 text-white shadow-sm shadow-green-900/10',
                'resultIcon' => 'text-white',
                'resultKicker' => 'text-green-700',
                'resultTitle' => 'text-green-900',
                'resultBodyText' => 'text-green-800/90',
                'panel' => 'border-green-200/60 bg-green-50/30',
                'panelBody' => 'bg-green-50/30',
                'summaryIcon' => 'text-green-600',
                'submissionBox' => 'bg-green-50 border border-green-100 text-green-800',
                'uploadFileButton' => 'file:bg-green-600',
                'uploadFileHoverButton' => 'hover:file:bg-green-700',
                'uploadInputBorderHover' => 'hover:border-green-300',
            ],
            'warning' => [
                'card' => 'border-amber-200/70 bg-amber-50/20',
                'accentBorder' => 'border-t-4 border-t-amber-500/70',
                'header' => 'bg-amber-50/60 border-amber-100',
                'icon' => 'bg-amber-600/10 text-amber-700',
                'accentText' => 'text-amber-800',
                'accentHoverText' => 'group-hover:text-amber-800',
                'resultStrip' => 'bg-gradient-to-r from-amber-50 to-white border-b border-amber-200/60',
                'resultIconBg' => 'bg-amber-600 text-white shadow-sm shadow-amber-900/10',
                'resultIcon' => 'text-white',
                'resultKicker' => 'text-amber-800',
                'resultTitle' => 'text-amber-950',
                'resultBodyText' => 'text-amber-900/90',
                'panel' => 'border-amber-200/60 bg-amber-50/30',
                'panelBody' => 'bg-amber-50/30',
                'summaryIcon' => 'text-amber-600',
                'submissionBox' => 'bg-amber-50 border border-amber-100 text-amber-800',
                'uploadFileButton' => 'file:bg-amber-600',
                'uploadFileHoverButton' => 'hover:file:bg-amber-700',
                'uploadInputBorderHover' => 'hover:border-amber-300',
            ],
            'error' => [
                'card' => 'border-red-200/70 bg-red-50/20',
                'accentBorder' => 'border-t-4 border-t-red-500/70',
                'header' => 'bg-red-50/60 border-red-100',
                'icon' => 'bg-red-600/10 text-red-700',
                'accentText' => 'text-red-700',
                'accentHoverText' => 'group-hover:text-red-700',
                'resultStrip' => 'bg-gradient-to-r from-red-50 to-white border-b border-red-200/60',
                'resultIconBg' => 'bg-red-600 text-white shadow-sm shadow-red-900/10',
                'resultIcon' => 'text-white',
                'resultKicker' => 'text-red-700',
                'resultTitle' => 'text-red-950',
                'resultBodyText' => 'text-red-900/90',
                'panel' => 'border-red-200/60 bg-red-50/30',
                'panelBody' => 'bg-red-50/30',
                'summaryIcon' => 'text-red-600',
                'submissionBox' => 'bg-red-50 border border-red-100 text-red-800',
                'uploadFileButton' => 'file:bg-red-600',
                'uploadFileHoverButton' => 'hover:file:bg-red-700',
                'uploadInputBorderHover' => 'hover:border-red-300',
            ],
            'neutral' => [
                'card' => 'border-slate-200 bg-white',
                'accentBorder' => 'border-t-4 border-t-slate-200',
                'header' => 'bg-slate-50 border-slate-100',
                'icon' => 'bg-slate-200/60 text-slate-600',
                'accentText' => 'text-ds-pink',
                'accentHoverText' => 'group-hover:text-ds-pink',
                'resultStrip' => 'bg-slate-50 border-b border-slate-200',
                'resultIconBg' => 'bg-slate-600 text-white',
                'resultIcon' => 'text-white',
                'resultKicker' => 'text-slate-600',
                'resultTitle' => 'text-slate-900',
                'resultBodyText' => 'text-slate-700',
                'panel' => 'border-slate-200 bg-slate-50/40',
                'panelBody' => 'bg-slate-50/40',
                'summaryIcon' => 'text-emerald-500',
                'submissionBox' => 'bg-slate-50 border border-slate-100 text-slate-700',
                'uploadFileButton' => 'file:bg-ds-pink',
                'uploadFileHoverButton' => 'hover:file:bg-blue-700',
                'uploadInputBorderHover' => 'hover:border-ds-pink/30',
            ],
        ];

        return $map[$v] ?? $map['neutral'];
    }
}
