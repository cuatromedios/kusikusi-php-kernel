<?php

namespace Cuatromedios\Kusikusi\Http\Controllers\Media;

use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Exceptions\ExceptionDetails;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Cuatromedios\Kusikusi\Models\Activity;
use Cuatromedios\Kusikusi\Providers\AuthServiceProvider;

class MediaController extends Controller
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
   * Gets a medium processed if its an image.
   *
   * @param $id
   * @param $preset
   * @return Response
   */
  public function get($id, $preset, $friendly = NULL)
  {
    //die("{$id} {$preset} {$friendly}");
    // TODO: Review if the user can read the media
    try {
      $entity = Entity::with('medium')->findOrFail($id);
    } catch (\Exception $e) {
      return (new ApiResponse(NULL, FALSE, 'Media ' . ApiResponse::TEXT_NOTFOUND, ApiResponse::STATUS_NOTFOUND))->response();
    }
    $presetSettings = Config::get('media.presets.' . $preset, NULL);
    if (NULL === $presetSettings) {
      return (new ApiResponse(NULL, FALSE, 'Preset ' . ApiResponse::TEXT_NOTFOUND, ApiResponse::STATUS_NOTFOUND))->response();
    }

    // Paths
    $originalFilePath =   $id . '/file.' . $entity->medium->format;
    $publicFilePath = $id . '/' .  $preset . '.' . $presetSettings['format'];

    // TODO:: We are returning from here the cached images, maybe use the webserver directly?
    if ($exists = Storage::disk('media_processed')->exists($publicFilePath)) {
      $cachedImage = Storage::disk('media_processed')->get($publicFilePath);
      return new Response(
          $cachedImage,  200,
          [
              'Content-Type' => Storage::disk('media_processed')->getMimeType($publicFilePath),
              'Content-Length' => Storage::disk('media_processed')->size($publicFilePath)
          ]
      );
    }
    if (!$exists = Storage::disk('media_original')->exists($originalFilePath)) {
      return (new ApiResponse(NULL, FALSE, 'Original media ' . ApiResponse::TEXT_NOTFOUND, ApiResponse::STATUS_NOTFOUND))->response();
    }
    $filedata = Storage::disk('media_original')->get($originalFilePath);

    if (array_search($entity['medium']['format'], ['jpg', 'png', 'gif']) === FALSE) {
      return new Response(
          $filedata,  200,
          [
              'Content-Type' => Storage::disk('media_original')->getMimeType($originalFilePath),
              'Content-Length' => Storage::disk('media_original')->size($originalFilePath)
          ]
      );
    }

    // Set default values if not set
    data_fill($presetSettings, 'width', 256);  // int
    data_fill($presetSettings, 'height', 256); // int
    data_fill($presetSettings, 'scale', 'cover'); // contain | cover | fill
    data_fill($presetSettings, 'alignment', 'center'); // only if scale is 'cover' or 'contain' with background: top-left | top | top-right | left | center | right | bottom-left | bottom | bottom-right
    data_fill($presetSettings, 'background', 'crop'); // only if scale is 'contain': crop | #HEXCODE
    data_fill($presetSettings, 'quality', 80); // 0 - 100 for jpg | 1 - 8, (bits) for gif | 1 - 8, 24 (bits) for png
    data_fill($presetSettings, 'format', 'jpg'); // jpg | gif | png
    data_fill($presetSettings, 'effects', []); // ['colorize' => [50, 0, 0], 'grayscale' => [] ]


    // The fun
    $image = Image::make($filedata);
    if ($presetSettings['scale'] === 'cover') {
      $image->fit($presetSettings['width'], $presetSettings['height'], NULL, $presetSettings['alignment']);
    } elseif ($presetSettings['scale'] === 'fill') {
      $image->resize($presetSettings['width'], $presetSettings['height']);
    } elseif ($presetSettings['scale'] === 'contain') {
      $image->resize($presetSettings['width'], $presetSettings['height'], function ($constraint) {
        $constraint->aspectRatio();
      });
      $matches = preg_match('/#([a-f0-9]{3}){1,2}\b/i', $presetSettings['background'], $matches);
      if ($matches) {
        $image->resizeCanvas($presetSettings['width'], $presetSettings['height'], $presetSettings['alignment'], false, $presetSettings['background']);
      }
    }

    foreach ($presetSettings['effects'] as $key => $value) {
      $image->$key(...$value);
    }

    $image->encode($presetSettings['format'], $presetSettings['quality']);
    Storage::disk('media_processed')->put($publicFilePath, $image);

    // Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'get', '{}');
    // return $image->response();
    $cachedImage = Storage::disk('media_processed')->get($publicFilePath);
    return new Response(
        $cachedImage,  200,
        [
            'Content-Type' => Storage::disk('media_processed')->getMimeType($publicFilePath),
            'Content-Length' => Storage::disk('media_processed')->size($publicFilePath)
        ]
    );
  }
}
