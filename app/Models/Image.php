<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $table = 'images';

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'extension',
        'url',
        'dimension',
        'month',
        'year',
    ];

    protected $casts = [
        'dimension' => 'array', // چون در دیتابیس به صورت JSON ذخیره میشه
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ارتباط با کاربر (در صورتی که user_id به users مربوط باشه)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function url()
    {

        if($this->year != '' && $this->month != '')
        {
            return '/uploads/images/'.$this->year.'/'.$this->month.'/'.$this->url;
        }
        else
        {

            return $this->url;
        }
    }
    public function path()
    {
        if($this->year != '' && $this->month != '')
        {
            $path = $this->year.'/'.$this->month.'/'.$this->url;
        }
        else
        {
            $path = $this->url;
        }
        return base_path() . '/upload/images/' . $path;
    }
}
