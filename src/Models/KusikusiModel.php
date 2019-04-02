<?php
namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

/**
 * Class KusikusiModel
 *
 * @package Cuatromedios\Kusikusi\Models
 */
class KusikusiModel extends Model
{

    /**
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
     * @var
     */
    protected $_lang;
    /**
     * @var array
     */
    protected $hidden = ['relatedContents'];
    /**
     * The primary key is the same id than the related EntityBase
     */
    protected $primaryKey = 'id';

    /**
     * KusikusiModel constructor.
     *
     * @param array $newAttributes
     *
     * @throws \Exception
     */
    public function __construct(array $newAttributes = [])
    {
        if (!isset($newAttributes['id'])) {
            $generatedId = Uuid::uuid4()->toString();
            $this->attributes['id'] = $generatedId;
        } else {
            $this->attributes['id'] = $newAttributes['id'];
        }
        parent::__construct($newAttributes);
    }

    /**
     * Get the class name as a kebab string
     *
     * @return string
     */
    public function instanceModelId()
    {
        return self::modelId();
    }

    /**
     * @return string
     */
    public static function modelId()
    {
        return $model['model'] = kebab_case(class_basename(get_called_class()));
    }

    /**
     * Get the activity related to the EntityBase.
     */
    public function activity()
    {
        return $this->hasMany('Cuatromedios\Kusikusi\Models\Activity', 'entity_id');
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array $models
     *
     * @return \Cuatromedios\Kusikusi\Models\KusikusiCollection
     */
    public function newCollection(array $models = [])
    {
        return new KusikusiCollection($models);
    }

}
