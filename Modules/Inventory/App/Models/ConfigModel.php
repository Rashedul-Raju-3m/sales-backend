<?php

namespace Modules\Inventory\App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Utility\App\Models\SettingModel;


class ConfigModel extends Model
{
    use HasFactory;

    protected $table = 'inv_config';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $fillable = [
        'business_model_id',
        'stock_format',
        'global_option_id',
        'printer',
        'address',
        'path',
        'unit_commission',
        'border_color',
        'is_stock_history',
        'print_footer_text',
        'invoice_comment',
        'vat_percent',
        'ait_percent',
        'font_size_label',
        'font_size_value',
        'vat_reg_no',
        'multi_company',
        'vat_enable',
        'bonus_from_stock',
        'condition_sales',
        'is_marketing_executive',
        'pos_print',
        'fuel_station',
        'zero_stock',
        'system_reset',
        'tlo_commission',
        'sr_commission',
        'sales_return',
        'store_ledger',
        'invoice_width',
        'print_top_margin',
        'print_margin_bottom',
        'header_left_width',
        'header_right_width',
        'print_margin_report_top',
        'is_print_header',
        'is_invoice_title',
        'print_outstanding',
        'is_print_footer',
        'invoice_prefix',
        'invoice_process',
        'customer_prefix',
        'production_type',
        'invoice_type',
        'border_width',
        'body_font_size',
        'sidebar_font_size',
        'invoice_font_size',
        'print_left_margin',
        'invoice_height',
        'left_top_margin',
        'is_unit_price',
        'body_top_margin',
        'sidebar_width',
        'body_width',
        'invoice_print_logo',
        'barcode_print',
        'custom_invoice',
        'custom_invoice_print',
        'show_stock',
        'is_powered',
        'remove_image',

    ];

    public function business_model(): BelongsTo
    {
        return $this->belongsTo(SettingModel::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class);
    }

}