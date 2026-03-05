<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Ai\Agents\ContentEnhancer;
use App\Http\Controllers\Controller;
use App\Http\Requests\EnhanceContentRequest;
use Illuminate\Http\JsonResponse;

class ExperienceEnhanceController extends Controller
{
    public function __invoke(EnhanceContentRequest $request): JsonResponse
    {
        $section = $request->input('section');
        $item = $request->input('item');

        $enhancer = new ContentEnhancer($section);
        $response = $enhancer->prompt(
            "Enhance the following {$section} content:\n\n".json_encode($item, JSON_PRETTY_PRINT)
        );

        return response()->json($response->toArray());
    }
}
