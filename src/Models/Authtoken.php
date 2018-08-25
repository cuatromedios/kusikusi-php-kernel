<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;

class Authtoken extends Model
{
    const AUTHORIZATION_HEADER = 'Authorization';
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
     * Get the Entity that owns the content.
     */
    public function user()
    {
        return $this->belongsTo('App\Models\Data\User', 'user_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'token', 'created_ip', 'updated_ip'
    ];


    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

}
