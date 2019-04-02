<?php
namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class Relation
 *
 * @package Cuatromedios\Kusikusi\Models
 */
class Relation extends Pivot
{
    /**
     * @var array
     */
    protected $hidden = ['caller_id', 'called_id', 'created_at', 'updated_at'];
    /**
     * @var array
     */
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
