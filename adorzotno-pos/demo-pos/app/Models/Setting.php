<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $table = "settings";
    protected $primaryKey = "id";
    public $timestamps = false;

    protected $fillable = [
        'company_name',       
        'header_logo',  
        'footer_logo', 
        'footer_text',
        'address',
        'news_letter_text',
        'google_map',
        'office_address', 
        'phone',
        'email',
        'footer_gateway_banner',
        'homepage_about_text',
        'homepage_notice',
    ];

    public static function get(string $key, $default = null)
    {
        $instance = new static();
        $table = $instance->getTable();

        if (!Schema::hasTable($table)) {
            return $default;
        }

        if (Schema::hasColumn($table, $key)) {
            return static::query()->value($key) ?? $default;
        }

        if (Schema::hasColumn($table, 'key') && Schema::hasColumn($table, 'value')) {
            return static::query()
                ->where('key', $key)
                ->value('value') ?? $default;
        }

        return $default;
    }
}
