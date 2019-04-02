<?php
namespace Cuatromedios\Kusikusi\Http\Controllers\Media;

use App\Models\Entity;
use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Class MediaController
 *
 * @package Cuatromedios\Kusikusi\Http\Controllers\Media
 */
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
     *
     * @return Image
     */
    public function get($id, $preset, $friendly = null)
    {
        //die("{$id} {$preset} {$friendly}");
        // TODO: Review if the user can read the media
        try {
            $entity = Entity::with('medium')->findOrFail($id);
        } catch (\Exception $e) {
            return (new ApiResponse(null, false, 'Media ' . ApiResponse::TEXT_NOTFOUND,
                ApiResponse::STATUS_NOTFOUND))->response();
        }
        $presetSettings = Config::get('media.presets.' . $preset, null);
        if (null === $presetSettings) {
            return (new ApiResponse(null, false, 'Preset ' . ApiResponse::TEXT_NOTFOUND,
                ApiResponse::STATUS_NOTFOUND))->response();
        }
        // Paths
        $originalFilePath = $id . '/file.' . $entity->medium->format;
        $publicFilePath = $id . '/' . $preset . '.' . $presetSettings['format'];
        // TODO:: We are returning from here the cached images, maybe use the webserver directly?
        if ($exists = Storage::disk('media_processed')->exists($publicFilePath)) {
            $cachedImage = Storage::disk('media_processed')->get($publicFilePath);

            return new Response(
                $cachedImage, 200,
                ['Content-Type' => Storage::disk('media_processed')->getMimeType($publicFilePath)]
            );
        }
        if (!$exists = Storage::disk('media_original')->exists($originalFilePath)) {
            return (new ApiResponse(null, false, 'Original media ' . ApiResponse::TEXT_NOTFOUND,
                ApiResponse::STATUS_NOTFOUND))->response();
        }
        $filedata = Storage::disk('media_original')->get($originalFilePath);
        if (array_search($entity['medium']['format'], ['jpg', 'png', 'gif']) === false) {
            return new Response(
                $filedata, 200,
                ['Content-Type' => Storage::disk('media_original')->getMimeType($originalFilePath)]
            );
        }
        // Set default values if not set
        data_fill($presetSettings, 'width', 256);  // int
        data_fill($presetSettings, 'height', 256); // int
        data_fill($presetSettings, 'scale', 'cover'); // contain | cover | fill
        data_fill($presetSettings, 'alignment',
            'center'); // only if scale is 'cover' or 'contain' with background: top-left | top | top-right | left | center | right | bottom-left | bottom | bottom-right
        data_fill($presetSettings, 'background', 'crop'); // only if scale is 'contain': crop | #HEXCODE
        data_fill($presetSettings, 'quality', 80); // 0 - 100 for jpg | 1 - 8, (bits) for gif | 1 - 8, 24 (bits) for png
        data_fill($presetSettings, 'format', 'jpg'); // jpg | gif | png
        data_fill($presetSettings, 'effects', []); // ['colorize' => [50, 0, 0], 'grayscale' => [] ]
        // The fun
        $image = Image::make($filedata);
        if ($presetSettings['scale'] === 'cover') {
            $image->fit($presetSettings['width'], $presetSettings['height'], null, $presetSettings['alignment']);
        } elseif ($presetSettings['scale'] === 'fill') {
            $image->resize($presetSettings['width'], $presetSettings['height']);
        } elseif ($presetSettings['scale'] === 'contain') {
            $image->resize($presetSettings['width'], $presetSettings['height'], function ($constraint) {
                $constraint->aspectRatio();
            });
            $matches = preg_match('/#([a-f0-9]{3}){1,2}\b/i', $presetSettings['background'], $matches);
            if ($matches) {
                $image->resizeCanvas($presetSettings['width'], $presetSettings['height'], $presetSettings['alignment'],
                    false, $presetSettings['background']);
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
            $cachedImage, 200,
            ['Content-Type' => Storage::disk('media_processed')->getMimeType($publicFilePath)]
        );
    }
}
