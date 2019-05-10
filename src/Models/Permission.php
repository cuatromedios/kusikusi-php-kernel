<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
   * Get the User that owns the content.
   */
  public function user()
  {
    return $this->belongsTo('App\Models\User', 'id');
  }

  /**
   * Get the Entity that owns the content.
   */
  public function entity()
  {
    return $this->belongsTo('Cuatromedios\Kusikusi\Models\EntityModel', 'entity_id');
  }

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'user_id', 'entity_id', 'read', 'write'
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
   * @param $user_id
   * @return Collection
   */
  public static function getPermissions($user_id)
  {
    $permissions = Permission::where('user_id', $user_id)->get();
    return $permissions;
  }

  /**
   * Post permissions.
   *
   * @param $information
   * @return \Illuminate\Http\JsonResponse|array
   */
  public static function addPermission($user_id, $entity_id, $read, $write)
  {
    $vars = get_defined_vars();
    Validator::make($vars, [
        "user_id" => "required",
        "entity_id" => "required",
        "read" => "required",
        "write" => "required|in:".Permission::NONE.",".Permission::OWN.",".Permission::ANY
    ], Config::get('validator.messages'))->validate();
    Permission::deletePermission($user_id, $entity_id);
    $result = Permission::updateOrCreate(
      [
        "user_id" => $user_id,
        "entity_id" => $entity_id,
      ],
      [
        "read" => $read,
        "write" => $write
    ]);
    return $result;
  }

  /**
   * Delete permissions.
   *
   * @param $user_id , $information
   * @return \Illuminate\Http\JsonResponse|array
   */
  public static function deletePermission($user_id, $entity_id)
  {
    $result = Permission::where('user_id', $user_id)->where('entity_id', $entity_id)->delete();
    return $result;
  }
}
