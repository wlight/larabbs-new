<?php

namespace App\Observers;

use App\Models\Link;
use App\Notifications\TopicReplied;
use Cache;

// creating, created, updating, updated, saving,
// saved,  deleting, deleted, restoring, restored

class LinkObserver
{
    public function saved(Link $link)
    {
        // 在保存的时候清空 cache_key 对应的缓存
        Cache::forget($link->cache_key);
    }
}