<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Relation extends Pivot
{
  protected $hidden = ['caller_id', 'called_id', 'created_at', 'updated_at'];
  protected $casts = [
      'tags' => 'array',
  ];

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'relations';
}