<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class KusikusiModel extends Model
{

  protected $_contents = [];
  protected $_lang;
  protected $hidden = ['relatedContents'];
  // protected $appends = ['contents'];

  public function __construct(array $newAttributes = array(), $lang = NULL)
  {
    if (!isset($newAttributes['id'])) {
      $generatedId =  Uuid::uuid4()->toString();
      $this->attributes['id'] = $generatedId;
    } else {
      $this->attributes['id'] = $newAttributes['id'];
    }
    if ($lang == NULL) {
      $this->setLang(Config::get('cms.langs')[0]);
    } else {
      $this->setLang($lang);
    }

    parent::__construct($newAttributes);
  }

  /**
   * The primary key is the same id than the related EntityBase
   */
  protected $primaryKey = 'id';
  public $incrementing = false;


  /**
   * Get the class name as a kebab string
   * @return string
   */
  public function instanceModelId()
  {
    return self::modelId();
  }
  public static function modelId()
  {
    return $model['model'] = kebab_case(class_basename(get_called_class()));
  }


  /**
   * Indicates  the model should be timestamped.
   * @var bool
   */
  public $timestamps = false;


  /**
   * Set the contents relation of the EntityBase.
   */
  public function relatedContents()
  {
    return $this->hasMany('Cuatromedios\Kusikusi\Models\EntityContent', 'entity_id');
  }

  /**
   * Mutator to create or update the contents alongside the related model
   * @param array $value
   */
  public function setContentsAttribute($contents) {
    //TODO: Is there a faster way to do it?
    foreach ($contents as $key => $value) {
      $keyType = gettype($key);
      $valueType = gettype($value);
      if ($keyType == "string" && $valueType == "string") {
        $this->_contents[$this->attributes['id']."_".$this->_lang."_".$key] = $value;
        //print("\n".$this->attributes['id']."_".$this->_lang."_".$key." = ".$value);
      } else if ($keyType == "string" && $valueType == "array") {
        $this->_contents[$this->attributes['id']."_".$key."_".key($value)] = $value[key($value)];
        //print("\n".$this->attributes['id']."_".$key."_".key($value)." = ".$value[key($value)]);
      }
    }
  }

  /**
   * Mutator to retrieve the contents alongside the related model
   * @return array Returns the related entity
   */
  public function getContentsAttribute() {
    $result = [];
    //TODO: Is there a faster way to do it?
    foreach ($this->_contents as $key => $value) {
      $keys = explode("_", $key);
      if ($keys[1] == $this->_lang) {
        $result[$keys[2]] = $value;
      }
    }
    return $result;
  }

  public function getRawContents() {
    $result = [];
    //TODO: Is there a faster way to do it?
    foreach ($this->_contents as $key => $value) {
      $keys = explode("_", $key);
      $result[] = [
          "id" => $key,
          "entity_id" => $keys[0],
          "lang" => $keys[1],
          "field" => $keys[2],
          "value" => $value,
      ];
    }
    return $result;
  }

  public function  clearContents() {
    $this->_contents = [];
  }

  public function getLang() {
    return $this->_lang;
  }

  public function setLang($lang) {
    $this->_lang = $lang;
  }

  /**
   * Get the activity related to the EntityBase.
   */
  public function activity()
  {
    return $this->hasMany('Cuatromedios\Kusikusi\Models\Activity', 'entity_id');
  }


  /**
   * The attributes excluded from the model's JSON form.
   * @var array
   */

  public static function boot() {

    parent::boot();

    self::saved(function (KusikusiModel $model) {
      foreach ($model->getRawContents() as $contentRow) {
        $where = ['id' => $contentRow['id']];
        $set = ['id' => $contentRow['id'], 'entity_id' => $contentRow['entity_id'], 'lang' => $contentRow['lang'], 'field' => $contentRow['field'], 'value' => $contentRow['value']];
        EntityContent::updateOrCreate($where, $set);
      }
    });

    self::retrieved(function (KusikusiModel $model) {
      foreach ($model->relatedContents as $contentRow) {
        $model->_contents[$contentRow->entity_id."_".$contentRow->lang."_".$contentRow->field] = $contentRow->value;
      }
    });
  }
}
