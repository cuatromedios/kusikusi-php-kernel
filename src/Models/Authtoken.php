<?php
namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;

class Authtoken extends Model
{
    const AUTHORIZATION_HEADER = 'Authorization';
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    /**
     * Indicates  the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'authtokens';
    /**
     * The primary key
     */
    protected $primaryKey = 'token';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'token',
        'created_ip',
        'updated_ip',
    ];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Get the EntityBase that owns the content.
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

}
