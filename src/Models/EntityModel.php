<?php

namespace Cuatromedios\Kusikusi\Models;

use App\Models\Entity;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntityModel extends KusikusiModel
{

  protected $_contents = [];
  protected $_lang;

  public function __construct(array $newAttributes = array(), $lang = NULL)
  {
    if ($lang == NULL) {
      $this->setLang(Config::get('cms.langs')[0]);
    } else {
      $this->setLang($lang);
    }
    parent::__construct($newAttributes);
  }


  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'entities';

  /**
   * The primary key
   */
  protected $primaryKey = 'id';

  /**
   * Indicates if the IDs are auto-incrementing and is not numeric.
   *
   * @var bool
   */
  public $incrementing = false;
  public $keyType = "string";

  /**
   * The attributes that are not mass assignable.
   *
   * @var array
   */
  protected $guarded = [
      'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'publicated_at', 'unpublicated_at', 'entity_version', 'tree_version', 'relations_version', 'full_version'
  ];

  /**
   * Active attribute should be casted to boolean
   * @var array
   */
  protected $casts = [
      'active' => 'boolean'
  ];

  /**
   * The model should use soft deletes.
   *
   * @var array
   */
  use SoftDeletes;

  /**
   * Indicates  the model should be timestamped.
   *
   * @var bool
   */
  public $timestamps = true;



  /**
   * Set the contents relation of the EntityBase.
   */
  public function contents()
  {
    return $this->hasMany('Cuatromedios\Kusikusi\Models\EntityContent', 'entity_id');
  }

  /**
   * Adds content rows to an Entity.
   *
   * @param  array $contents An arrray of one or more contents in field => value format for example ["title" => "The Title", "summary", "The Summary"]
   * @param  string $lang optional language code, for example "en" or "es-mx"
   */
  public function addContents($contents, $lang = NULL)
  {
    $lang = $lang ?? $this->_lang ?? Config::get('cms.langs')[0] ?? '';
    foreach ($contents as $key=>$value) {
      EntityContent::updateOrCreate(
          [
            "id" => "{$this->id}_{$lang}_{$key}"
          ], [
            "entity_id" => $this->id,
            "field" => $key,
            "value" => $value,
            "lang" => $lang
          ]);
    }
  }
  /**
   * Deletes content rows to an Entity.
   *
   * @param  array $fields An arrray of one or more field names
   * @param  string $lang optional language code, for example "en" or "es-mx"
   */
  public function deleteContents($fields, $lang = NULL)
  {
    if (is_string($fields)) {
      $fields = [$fields];
    }
    $lang = $lang ?? $this->_lang ?? Config::get('cms.langs')[0] ?? '';
    $idstodelete = [];
    foreach ($fields as $field) {
      $idstodelete[] = "{$this->id}_{$lang}_{$field}";
    }
    EntityContent::destroy($idstodelete);
  }

  public static function compact($array) {
    //TODO: This may be very inneficient :S
    if (!is_array($array)) {
      $array = $array->toArray();
    }
    foreach ($array as $key => $value) {
      if ($key === "contents") {
        $compactedContents = [];
        foreach ($value as $content) {
          $compactedContents[$content['field']] = $content['value'];
        };
        $array[$key] = $compactedContents;
      } else if (is_array($value)) {
        $array[$key] = EntityModel::compact($value);
      }
    }
    return $array;
  }

  public function getLang() {
    return $this->_lang;
  }

  public function setLang($lang) {
    $this->_lang = $lang;
  }


  /**************************
   *
   * SCOPES
   *
   **************************/

  /**
   * Scope a query to only include entities of a given modelId.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  mixed $modelId
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeOfModel($query, $modelId)
  {
    return $query->where('model', $modelId);
  }

  /**
   * Scope a query to only include published entities.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeIsPublished($query)
  {
    //TODO: Check correctrly the dates
    return $query->where('active', true)->where('publicated_at', '2000')->where('unpublicated_at', 2000)->where('deleted_at');
  }

  /**
   * Scope a query to only include childs of a given parent id.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $entity_id The id of the parent entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeChildOf($query, $parent_id)
  {
    return $query->where('parent_id', $parent_id);
  }

  /**
   * Scope a query to only include entities of a given modelId.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $content_fields The id of the Entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWithContents($query)
  {
    $content_fields = params_as_array(func_get_args(), 1);
    if (count($content_fields) == 0) {
      $query->with('contents');
    } else {
      $query->with(['contents' => function($query) use ($content_fields) {
        $first = true;
        foreach ($content_fields as $field) {
          if ($first) {
            $query->where('field', '=', $field);
            $first = false;
          } else {
            $query->orWhere('field', '=', $field);
          }
        }
      }]);
    }

    return $query;
  }
  /**
   * Scope a query to only include relations having specific tags.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWhereTags($query)
  {
    $tags = join(" ", params_as_array(func_get_args(), 1));
    $query->whereRaw("MATCH (tags) AGAINST (? IN BOOLEAN MODE)" , $tags);
    return $query;
  }


  public function scopeWithRelations($query, $function = NULL) {
    if (NULL == $function) {
      return $query->with("relations");
    }
    return $query->with(["relations" => $function]);
  }

  /**************************
   *
   * RELATIONS
   *
   **************************/

  /**
   * The relations that belong to the entity.
   */
  public function relations()
  {
    return $this
        ->belongsToMany('App\Models\Entity', 'relations', 'caller_id', 'called_id')
        ->using('Cuatromedios\Kusikusi\Models\Relation')
        ->as('relation')
        ->withPivot('kind', 'position', 'depth', 'tags')
        ->withTimestamps();
  }

  /**
   * Dinamically creates relations
   */
  public function addRelation($data)
  {
    if (isset($data['id'])) {
      $id = $data['id'];
      unset($data['id']);
      if (!isset($data['kind'])) {
        $data['kind'] = 'relation';
      }
      if (!isset($data['position'])) {
        $data['position'] = 0;
      }
      if (!isset($data['tags'])) {
        $data['tags'] = [];
      }
      if (is_string($data['tags'])) {
        $data['tags'] = explode(',', $data['tags']);
      }
      if (!isset($data['depth'])) {
        $data['depth'] = 0;
      }
      if (count($this->relations()->where(['called_id' => $id, 'kind' => $data['kind']])->get()) > 0) {
        $this->relations()->updateExistingPivot($id, $data);
      } else {
        $this->relations()->attach($id, $data);
      }
      return ['id' => $id];
    }
  }

  /**
   * Events.
   *
   * @var bool
   */

  public static function boot($preset = [])
  {

    parent::boot();

    self::creating(function ($model) {
      if (!isset($model['model'])) {
        throw new \Exception('A model id is requiered to create a new entity', ApiResponse::STATUS_BADREQUEST);
      }
    });


    self::created(function ($entity) {
      // Create the ancestors relations
      if (isset($entity['parent_id']) && $entity['parent_id'] != NULL) {
        $parentEntity = Entity::find($entity['parent_id']);
        $entity->addRelation(['id' =>$parentEntity['id'], 'kind' => 'ancestor', 'depth' => 1]);
        $ancestors = ($parentEntity->relations()->where('kind', 'ancestor')->orderBy('depth'))->get();
        for ($a = 0; $a < count($ancestors); $a++) {
          $entity->addRelation(['id' => $ancestors[$a]['id'], 'kind' => 'ancestor', 'depth' => ($a + 2)]);
        }
      };
    });

  }

  /**
   * Updates the entity version, tree version and full version of the given entity
   * as well as it´s ancestors (and inverse relations)
   * @param $entity
   */
  private static function updateEntityVersion($entity)
  {
    // Updates the version of the own entity and its full version as well
    DB::table('entities')->where('id', $entity)
        ->increment('entity_version');
    DB::table('entities')->where('id', $entity)
        ->increment('full_version');
    // Then the three version (and full version), using its ancestors
    $ancestors = self::getAncestors($entity, ['e.id']);
    if (!empty($ancestors)) {
      DB::table('entities')->whereIn('id', $ancestors)
          ->increment('tree_version');
      DB::table('entities')->whereIn('id', $ancestors)
          ->increment('full_version');
    }

    // Now updates the tree and full version of the relations entity's ancestors and the relation version of the given entity
    $relateds = self::getInverseEntityRelations($entity, NULL, ['e.id']);
    foreach ($relateds as $related) {
      $ancestors = self::getAncestors($related, ['e.id']);
      if (!empty($ancestors)) {
        DB::table('entities')->whereIn('id', $ancestors)
            ->increment('tree_version');
        DB::table('entities')->whereIn('id', $ancestors)
            ->increment('full_version');
      }
    }
    if (!empty($relateds)) {
      DB::table('entities')->whereIn('id', $relateds)
          ->increment('relations_version');
    }
  }

  /**
   * Updates the relation version of the caller entity and updates
   * the tree version and full version of the called entity and it's
   * ancestors
   * @param $caller
   * @param $called
   */
  private static function updateRelationVersion($caller, $called)
  {
    // Update the tree and full version of the called entity (and it's ancestors)
    $relateds = self::getInverseEntityRelations($called, NULL, ['e.id']);
    foreach ($relateds as $related) {
      $ancestors = self::getAncestors($related, ['e.id']);
      if (!empty($ancestors)) {
        DB::table('entities')->whereIn('id', $ancestors)
            ->increment('tree_version');
        DB::table('entities')->whereIn('id', $ancestors)
            ->increment('full_version');
      }
    }
    // Now update the relation_version of the caller entity
    if (!empty($relateds)) {
      DB::table('entities')->whereIn('id', $relateds)
          ->where('id', $caller)
          ->increment('relations_version');
    }
  }

  /**
   *  Return a class from a string
   */
  private static function getClassFromModelId($modelId)
  {
    if (isset($modelId) && $modelId != '') {
      return ("App\\Models\\" . (studly_case($modelId)));
    } else {
      return NULL;
    }
  }

}
