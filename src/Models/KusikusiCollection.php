<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Collection;
use RecursiveIteratorIterator;
use RecursiveArrayIterator;

class KusikusiCollection extends Collection
{
  /**
   * Returns the collection as pretty print Json
   * @return string
   */
  public function toPrettyJson()
  {
    return $this->toJson(JSON_PRETTY_PRINT);
  }

  /**
   * Returns the collection as an array, with the content fields compacted in "field" => "value" format
   * @return string
   */
  public function compact()
  {
    return EntityModel::compactContents($this);
  }
}
