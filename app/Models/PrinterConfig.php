<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterConfig extends Model
{
    protected $fillable = [
        'name',
        'paper_size',
        'paper_width_px',
        'is_default',
        'auto_print',
        'margins',
        'font_family',
        'font_size_px',
        'show_logo',
        'show_footer',
    ];

    protected $casts = [
        'paper_width_px' => 'integer',
        'is_default' => 'boolean',
        'auto_print' => 'boolean',
        'margins' => 'array',
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
     * Get CSS width value for printing.
     */
    public function getCssWidthAttribute(): string
    {
        return match ($this->paper_size) {
            '58mm' => '58mm',
            '80mm' => '80mm',
            'custom' => ($this->paper_width_px ?? 200) . 'px',
            default => '58mm',
        };
    }

    /**
     * Get CSS for print media.
     */
    public function getPrintCssAttribute(): string
    {
        $margins = $this->margins ?? ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];
        
        return "
            @page {
                size: {$this->css_width} auto;
                margin: {$margins['top']}mm {$margins['right']}mm {$margins['bottom']}mm {$margins['left']}mm;
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
