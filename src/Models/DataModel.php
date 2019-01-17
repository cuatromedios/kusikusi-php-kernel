<?php

namespace Cuatromedios\Kusikusi\Models;

use App\Models\Entity;

class DataModel extends KusikusiModel
{

  public $modelId = 'nomodel';

  public function __construct(array $newAttributes = array(), $lang = NULL)
  {
    parent::__construct($newAttributes, $lang);
  }

  /**
   * Set the relation to an Entity.
   */
  public function entity() {
    return $this->belongsTo('App\Models\Entity', 'id');
  }

}
