<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileDownloadLink extends Model
{
    public $timestamps = false;

    /** @var array */
    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
            $model->token      = self::randString();
        });
    }

    /**
     * @param  int  $length
     * @param  string  $chars
     *
     * @return string
     * @throws \Exception
     */
    private static function randString(
        int $length = 64,
        string $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string {
        $result = '';
        $max    = mb_strlen($chars, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $result .= $chars[random_int(0, $max)];
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function file()
    {
        return $this->belongsTo(File::class)->withTrashed();
    }
}
