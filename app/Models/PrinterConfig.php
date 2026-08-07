<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterConfig extends Model
{
    protected $fillable = [
        'name',
        'paper_size',
        'paper_width_px',
        'paper_width',
        'paper_unit',
        'is_default',
        'auto_print',
        'margins',
        'margin_top',
        'margin_right',
        'margin_bottom',
        'margin_left',
        'font_family',
        'font_size_px',
        'show_logo',
        'show_footer',
    ];

    protected $casts = [
        'paper_width_px' => 'integer',
        'paper_width' => 'decimal:2',
        'is_default' => 'boolean',
        'auto_print' => 'boolean',
        'margins' => 'array',
        'margin_top' => 'decimal:2',
        'margin_right' => 'decimal:2',
        'margin_bottom' => 'decimal:2',
        'margin_left' => 'decimal:2',
        'font_size_px' => 'integer',
        'show_logo' => 'boolean',
        'show_footer' => 'boolean',
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Lebar kertas untuk CSS, dalam satuan pilihan admin (px/mm/cm).
     */
    public function getCssWidthAttribute(): string
    {
        return $this->paper_width . $this->paper_unit;
    }

    /**
     * Margin @page untuk CSS (atas kanan bawah kiri), dalam satuan yang sama
     * dengan lebar kertas.
     */
    public function getCssMarginAttribute(): string
    {
        $unit = $this->paper_unit;

        return "{$this->margin_top}{$unit} {$this->margin_right}{$unit} {$this->margin_bottom}{$unit} {$this->margin_left}{$unit}";
    }

    /**
     * Get CSS for print media.
     */
    public function getPrintCssAttribute(): string
    {
        return "
            @page {
                size: {$this->css_width} auto;
                margin: {$this->css_margin};
            }
            #receipt-print {
                width: {$this->css_width};
                font-family: {$this->font_family};
                font-size: {$this->font_size_px}px;
            }
        ";
    }

    /**
     * Set this config as default (unset others).
     */
    public function setAsDefault(): bool
    {
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        return $this->update(['is_default' => true]);
    }
}
