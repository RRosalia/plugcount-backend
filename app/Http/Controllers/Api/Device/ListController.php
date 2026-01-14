<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Resources\DeviceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $devices = $request->user()->devices()->latest()->get();

        return DeviceResource::collection($devices);
    }
}
