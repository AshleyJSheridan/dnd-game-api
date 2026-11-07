<?php

use App\Models\Campaign;
use App\Models\CampaignMap;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('campaign-map.{mapGuid}', function ($user, string $mapGuid) {
    $map = CampaignMap::where('guid', $mapGuid)->first();
    if (! $map) return false;

    $campaign = Campaign::find($map->game_id);

    return $campaign->user_id === $user->id                                     // DM
        || $campaign->Characters()->where('user_id', $user->id)->exists();      // player with a character in the campaign
}, ['guards' => ['api']]);
