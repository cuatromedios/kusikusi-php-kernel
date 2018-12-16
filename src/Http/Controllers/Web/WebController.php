<?php

namespace Cuatromedios\Kusikusi\Http\Controllers\Web;

use App\Models\Entity;
use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Models\EntityBase;
use Cuatromedios\Kusikusi\Models\EntityContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    //
  }

  /**
   * Gets the raw request and search for the corresponing entity to know its model.
   *
   * @param $request \Illuminate\Http\Request
   * @return \Illuminate\Http\Response
   */
  public function any(Request $request)
  {
    $query = ($request->query());
    $url = $request->path() == '/' ? '/' : '/' . $request->path();
    $format = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    if ($format === '') {
      $format = 'html';
    } else {
      $url = substr($url, 0, strrpos($url, "."));
    }

    // Search for the entity is being called by its url, ignore inactive and soft deleted.
    $search_result = EntityContent::where([["value", "=", $url], ["field", "=", "url"]])->first();
    if (!$search_result) {
      return ('404 not found');
    }
    $entity = Entity::findOrFail($search_result->entity_id);
    if (!$entity->isPublished()) {
      return ('Temporary not available');
    }
    $method_name = $entity->model;
    $controllerClassName = ucfirst($format) . 'Controller';
    require_once(base_path('app/Controllers/Web/' . $controllerClassName . ".php"));
    $controller = new \app\Controllers\Web\HtmlController;
    if (method_exists($controller, $method_name)) {
      return ($controller->$method_name($request, $entity));
    } else {
      //TODO: Send 404 or what the controller sends as 404 (for example a json error)
      //TODO: Hide sensitive data if not in debug mode?
      return ("Error: method '" . $method_name . "' not found in app/Controllers/Web/" . $controllerClassName);
    }
  }
}
