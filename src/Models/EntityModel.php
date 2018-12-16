<?php

namespace Cuatromedios\Kusikusi\Models;

use App\Models\Entity;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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

  protected $hidden = ['caller_id', 'called_id'];

  /**
   * Active attribute should be casted to boolean
   * @var array
   */
  protected $casts = [
      'active' => 'boolean',
      'tags' => 'array'
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

  public function compact() {
    return EntityModel::compactContents($this);
  }

  public static function compactContents($array) {
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
        $array[$key] = EntityModel::compactContents($value);
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
   * ACCESORS
   * 
   *************************/

   /**
    * Creates a virtual field where it determinates whether the entity is published or not
    *
    * @return boolean
    */
    public function getPublishedAttribute()
    {
      $currentDate = Carbon::now();
      if ($this->active === true && $this->publicated_at <= $currentDate && $this->unpublicated_at > $currentDate && $this->deleted_at === NULL) {
        return true;
      }
      return false;
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
    return $query->where('active', true)->whereDate('publicated_at', '<=', Carbon::now())->whereDate('unpublicated_at', '>', Carbon::now())->where('deleted_at');
  }

  /**
   * Scope a query to only include children of a given parent id.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $entity_id The id of the parent entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeChildOf($query, $parent_id)
  {
    $query->join('relations as rel_child', function ($join) use ($parent_id) {
      $join->on('rel_child.caller_id', '=', 'id')
          ->where('rel_child.called_id', '=', $parent_id)
          ->where('rel_child.depth', '=', 1)
          ->where('rel_child.kind', '=', 'ancestor')
      ;
    });
  }

  /**
   * Scope a query to only include the parent of the given id.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $entity_id The id of the parent entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeParentOf($query, $id)
  {
    $query->join('relations as rel_par', function ($join) use ($id) {
      $join->on('rel_par.called_id', '=', 'id')
          ->where('rel_par.caller_id', '=', $id)
          ->where('rel_par.depth', '=', 1)
          ->where('rel_par.kind', '=', 'ancestor')
      ;
    });
  }

  /**
   * Scope a query to only include ancestors of a given entity.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $entity_id The id of the parent entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeAncestorOf($query, $entity_id, $order = 'desc')
  {
    if ($order != 'asc') {
      $order = 'desc';
    }
    $query->join('relations as rel_anc', function ($join) use ($entity_id) {
      $join->on('rel_anc.called_id', '=', 'id')
          ->where('rel_anc.caller_id', '=', $entity_id)
          ->where('rel_anc.kind', '=', 'ancestor')
          ;
    })->orderBy('rel_anc.depth', $order);
  }

  /**
   * Scope a query to only include children of a given parent id.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $entity_id The id of the parent entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeDescendantOf($query, $entity_id, $order = 'desc', $depth = NULL)
  {
    if ($order != 'asc') {
      $order = 'desc';
    }
    if ($depth == NULL) {
      $depth = 99;
    }
    $query->join('relations as rel_des', function ($join) use ($entity_id, $depth) {
      $join->on('rel_des.caller_id', '=', 'id')
          ->where('rel_des.called_id', '=', $entity_id)
          ->where('rel_des.kind', '=', 'ancestor')
          ->where('rel_des.depth', '<=', $depth)
          ;
    })->orderBy('rel_des.depth', $order);
  }

  /**
   * Scope a query to only get entities being called by.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $entity_id The id of the entity calling the relations
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeRelatedBy($query, $entity_id)
  {
    $query->join('relations as rel_by', function ($join) use ($entity_id) {
      $join->on('rel_by.called_id', '=', 'id')
          ->where('rel_by.caller_id', '=', $entity_id)
          ->where('rel_by.kind', '!=', 'ancestor');
    })->addSelect('rel_by.kind', 'rel_by.position', 'rel_by.depth', 'rel_by.tags');
  }

  /**
   * Scope a query to only get entities being called by another of type medium.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $entity_id The id of the entity calling the relations
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeMediaOf($query, $entity_id)
  {
    $query->join('relations as rel_by', function ($join) use ($entity_id) {
      $join->on('rel_by.called_id', '=', 'id')
          ->where('rel_by.caller_id', '=', $entity_id)
          ->where('rel_by.kind', '=', 'medium');
    })
        ->addSelect('id', 'model', 'rel_by.kind', 'rel_by.position', 'rel_by.depth', 'rel_by.tags')
        ->orderBy('position')
        ->withContents('title')
        ->with('medium');
  }

  /**
   * Scope a query to only include relations where the given id is called.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $entity_id The id of the parent entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  // public function scopeInverseRelationsOf($query, $entity_id, $order = 'desc')
  // {
  //   if ($order != 'asc') {
  //     $order = 'desc';
  //   }
  //   $query->join('relations as rel_invR', function ($join) use ($entity_id) {
  //     $join->on('rel_invR.caller_id', '=', 'id')
  //         ->where('rel_invR.called_id', '=', $entity_id)
  //         ;
  //   })->orderBy('rel_invR.depth', $order);
  // }

  /**
   * Scope a query to include the contents.
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
   * Scope a query include relations of type medium.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $tags The id of the Entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWithMedia($query, $tags = NULL)
  {
    $tags = params_as_array(func_get_args(), 1);
    $query->withRelations(function($query) use ($tags) {
      $query->select('id')
          ->whereModel('medium')
          ->whereKind('medium');
          if (count($tags) > 0) {
            $query->whereTags($tags);
          }
          $query->with(['medium' => function($query) {
          }])
          ->orderBy('position', 'asc')
          ->withContents('title');
    });
    return $query;
  }


  /**
   * Scope a query to order by a content field.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @param  string $content_fields The id of the Entity
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeOrderByContents($query, $field, $order = "asc")
  {
    if ($order != 'desc') {
      $order = 'asc';
    }
    $query->join('contents as content_for_order', function ($join) use ($field) {
      $join->on('content_for_order.entity_id', '=', 'entities.id')
          ->where('field', '=', $field)
      ;
    });
    //$query->addSelect("contents.value as orderField");
    $query->orderBy("content_for_order.value", $order);
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

  /**
   * Appends relations of an Entity.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return function Function that receives and returns a query
   */
  public function scopeWithRelations($query, $function = NULL) {
    if (NULL == $function) {
      return $query->with("relations");
    }
    return $query->with(["relations" => $function]);
  }

  /**
   * Appends inverse relations of an Entity.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return function Function that receives and returns a query
   */
  public function scopeWithInverseRelations($query, $function = NULL) {
    if (NULL == $function) {
      return $query->with("inverseRelations");
    }
    return $query->with(["inverseRelations" => $function]);
  }

  /**
   * Appends the parent of an Entity.
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return function Function that receives and returns a query
   */
  public function scopeWithParent($query, $function = NULL) {
    $query->addSelect('parent_id');
    if (NULL == $function) {
      return $query->with('parent');
    }
    return $query->with(["parent" => $function])
        ->WithRelationData(function($filter) {
          $filter->where('called_id', $this->parent_id);
        });
  }

  /**
   * Appends relation data of an Entity
   */
  public function scopeWithRelationData($query, $function = NULL) {
    if (NULL == $function) {
      return $query->with("relationData");
    }
    return $query->with(["relationData" => $function]);
  }

  /**************************
   *
   * RELATIONS
   *
   **************************/


  /**
   * Set the contents relation of the EntityBase.
   */
  public function contents()
  {
    return $this->hasMany('Cuatromedios\Kusikusi\Models\EntityContent', 'entity_id');
  }

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
   * The inverse relations that belong to the entity.
   */
  public function inverseRelations()
  {
    return $this
        ->belongsToMany('App\Models\Entity', 'relations', 'called_id', 'caller_id')
        ->using('Cuatromedios\Kusikusi\Models\Relation')
        ->as('inverseRelations')
        ->withPivot('kind', 'position', 'depth', 'tags')
        ->withTimestamps();
  }

  /**
   * Get the activity related to the Entity.
   */
  public function activity()
  {
    return $this->hasMany('Cuatromedios\\Kusikusi\\Models\\Activity', 'entity_id');
  }

  /**
   * The parent of the entity.
   */
  public function parent()
  {
    return $this
        ->belongsTo('App\Models\Entity', 'parent_id');
  }

  /**
   * The relation data between two entities
   */
  public function relationData()
  {
    return $this
        ->hasMany('Cuatromedios\Kusikusi\Models\Relation', 'called_id');
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
      self::updateRelationVersion($this->id, $id);
      return ['id' => $id];
    }
  }

  /**
   * Updates the entity version, tree version and full version of the given entity
   * as well as it´s ancestors (and inverse relations)
   * @param $id
   */
  private static function updateEntityVersion($entity_id)
  {
    // Updates the version of the own entity and its full version as well
    DB::table('entities')
        ->where('id', $entity_id)
        ->increment('entity_version');
    DB::table('entities')
        ->where('id', $entity_id)
        ->increment('full_version');
    // Then the three version (and full version), using its ancestors
    $ancestors = Entity::select()->ancestorOf($entity_id)->get();
    if (!empty($ancestors)) {
      foreach ($ancestors as $ancestor) {
        DB::table('entities')
          ->where('id', $ancestor['id'])
          ->increment('tree_version');
        DB::table('entities')
          ->where('id', $ancestor['id'])
          ->increment('full_version');
      }
    }

    // Now updates the tree and full version of the relations entity's ancestors and the relation version of the given entity
    $relateds = Entity::where('id', $entity_id)->withInverseRelations()->get()->compact();
    if (count($relateds[0]['inverse_relations']) > 0) {
      foreach ($relateds[0]['inverse_relations'] as $related) {
        $ancestors = Entity::select()->ancestorOf($related['id'])->get();
        if (!empty($ancestors)) {
          foreach ($ancestors as $ancestor) {
            DB::table('entities')
              ->where('id', $ancestor['id'])
              ->increment('tree_version');
            DB::table('entities')
              ->where('id', $ancestor['id'])
              ->increment('full_version');
          }
        }
        DB::table('entities')
          ->where('id', $related['id'])
          ->increment('relations_version');
      }
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
    $relateds = Entity::where('id', $called)->withInverseRelations()->get()->compact();
    if (!empty($related)) {
      if (count($relateds[0]['inverse_relations']) > 0) {
        foreach ($relateds[0]['inverse_relations'] as $related) {
          $ancestors = Entity::select()->ancestorOf($related['id'])->get();
          if (!empty($ancestors)) {
            foreach ($ancestors as $ancestor) {
                DB::table('entities')
                 ->where('id', $ancestor['id'])
                 ->increment('tree_version');
                DB::table('entities')
                 ->where('id', $ancestor['id'])
                 ->increment('full_version');
            }
          }
        }
        // Now update the relation_version of the caller entity
        DB::table('entities')->whereIn('id', $relateds[0]['inverse_relations'])
        ->where('id', $caller)
        ->increment('relations_version');
      }
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

    self::saved(function ($entity) {
      self::updateEntityVersion($entity->id);
    });
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
