<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{

    const NONE = 'none';
    const OWN = 'own';
    const ANY = 'any';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    protected $primaryKey = 'user_id';


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
    public $timestamps = false;

    /**
     * Get the Entity that owns the content.
     */
    public function user()
    {
        return $this->belongsTo('App\Models\Data\User', 'entity_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'entity_id', 'get', 'post', 'patch', 'delete'
    ];


    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Get permissions.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|array
     */
    public static function getPermissions($id) {
        $user = Permission::where('user_id', $id)->firstOrFail();
        $user->get();
        return $user;
    }

    /**
     * Post permissions.
     *
     * @param $information
     * @return \Illuminate\Http\JsonResponse|array
     */
    public static function postPermissions($information) {
        $user = Permission::create($information);
        return $user;
    }

    /**
     * Patch permissions.
     *
     * @param $user_id, $information
     * @return \Illuminate\Http\JsonResponse|array
     */
    public static function patchPermissions($user_id, $information) {
        $user = Permission::where('user_id', $user_id)->firstOrFail();
        $user->update($information);
        return $user;
    }
}
