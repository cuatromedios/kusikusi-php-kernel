<?php
namespace Cuatromedios\Kusikusi\Models;

/**
 * Class DataModel
 *
 * @package Cuatromedios\Kusikusi\Models
 */
class DataModel extends KusikusiModel
{

    /**
     * @var string
     */
    public $modelId = 'nomodel';

    /**
     * DataModel constructor.
     *
     * @param array $newAttributes
     * @param null $lang
     */
    public function __construct(array $newAttributes = [], $lang = null)
    {
        parent::__construct($newAttributes, $lang);
    }

    /**
     * Set the relation to an Entity.
     */
    public function entity()
    {
        return $this->belongsTo('App\Models\Entity', 'id');
    }

}
