<?php

namespace Polyether\Support\Traits;

use Cache;

trait CacheHelperTrait
{
    protected $cacheKeys = [];

    public function setCacheKey ($key, $tag = 'default')
    {
        $this->cacheKeys[ $tag ][] = $key;
        return $key;
    }

    public function flushCache ($tag = null)
    {
        if (null == $tag) {
            if (is_array($this->cacheKeys)) {
                foreach ($this->cacheKeys as $tag) {
                    $this->forgetKeys($tag);
                }
            }
        } else {

            if (isset($this->cacheKeys[ $tag ]))
                $this->forgetKeys($this->cacheKeys[ $tag ]);

        }

    }

    protected function forgetKeys ($keys)
    {
        if (is_array($keys))
            foreach ($keys as $key) {
                if (Cache::has($key))
                    Cache::forget($key);
            }
    }

}