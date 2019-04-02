<?php
namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * Class Activity
 *
 * @package Cuatromedios\Kusikusi\Models
 */
class Activity extends Model
{

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
    protected $table = 'activity';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'entity_id',
        'action',
        'isSuccess',
        'subaction',
        'metadata',
    ];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * @param $user_id
     * @param $entity_id
     * @param $action
     * @param bool $isSuccess
     * @param null $subaction
     * @param null $metadata
     */
    public static function add($user_id, $entity_id, $action, $isSuccess = true, $subaction = null, $metadata = null)
    {
        if (Config::get('activity.log.' . $action,
                false) && ($isSuccess || ($isSuccess === false && Config::get('activity.log.error', false)))) {
            Activity::create([
                "user_id"   => $user_id,
                "entity_id" => $entity_id,
                "action"    => $action,
                "isSuccess" => $isSuccess,
                "subaction" => $subaction,
                "metadata"  => $metadata,
            ]);
        }
    }

    /**
     * Get the User that owns the content.
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /**
     * Get the EntityBase that owns the content.
     */
    public function entity()
    {
        return $this->belongsTo('Cuatromedios\Kusikusi\Models\EntityBase', 'entity_id');
    }

}
