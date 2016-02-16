<?php

namespace Polyether\Option;

use Cache;
use Illuminate\Database\Eloquent\Collection;
use Polyether\Option\Repositories\OptionRepository;
use Polyether\Support\Traits\CacheHelperTrait;

class OptionAPI
{

    use CacheHelperTrait;

    protected $option;
    protected $autoload = [];
    protected $cached = [];
    protected $notoption = [];

    public function __construct (OptionRepository $option)
    {
        $this->option = $option;
        $this->autoloadOptions();
    }

    public function autoloadOptions ()
    {
        $cache_key = $this->setCacheKey('options_autoload');

        if (Cache::has($cache_key)) {
            $this->autoload = Cache::get($cache_key);
        } else {
            $options = $this->option->findWhere(['autoload' => 'yes'], ['option_name', 'option_value']);

            if ($options instanceof Collection) {
                foreach ($options as $option) {
                    $this->autoload[ $option->option_name ] = $option->option_value;
                }
                Cache::put($cache_key, $this->autoload, $this->get('options_cache_expires', 60));
            }
        }
    }

    public function get ($name, $default = false)
    {
        $cache_key = $this->setCacheKey('option_' . md5($name));

        if (isset($this->notoption[ $name ])) {
            if ($default)
                return $default;
            return false;
        }


        if (isset($this->autoload[ $name ])) {
            $value = $this->autoload[ $name ];
        } else if (FALSE !== ($cachedOption = $this->getCached($name))) {
            $value = $cachedOption;
        } else if (Cache::has($cache_key)) {
            $value = Cache::get($cache_key);
        } else {
            $query = $this->option->findBy('option_name', $name);

            if ($query) {
                $value = $query->option_value;
                $this->cache($name, $value);
                Cache::put($cache_key, $value, $this->get('options_cache_expires', 60));
            } else {
                $this->notoption[ $name ] = true;
                if ($default)
                    return $default;
                return false;
            }
        }

        if ($this->isJSON($value)) {
            return json_decode($value);
        }
        return $value;
    }

    protected function getCached ($name)
    {
        return isset($this->cached[ $name ]) ? $this->cached[ $name ] : false;
    }

    protected function cache ($name, $value)
    {
        $this->cached[ $name ] = $value;
    }

    public function isJSON ($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    public function set ($name, $value, $autoload = 'yes')
    {

        if (true === $autoload)
            $autoload = 'yes';

        if (false === $autoload)
            $autoload = 'no';

        if (is_array($value) || is_object($value))
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);

        if ($this->option->create(['option_name' => $name, 'option_value' => $value, 'autoload' => $autoload])) {
            $this->flushCache();
            return TRUE;
        }
        return FALSE;
    }

    public function update ($name, $value, $autoload = null)
    {

        if (true === $autoload)
            $autoload = 'yes';

        if (false === $autoload)
            $autoload = 'no';

        if (is_array($value) || is_object($value))
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);


        $updated_options = ['option_name' => $name, 'option_value' => $value];

        if ($autoload !== null)
            $updated_options[ 'autoload' ] = $autoload;

        if ($this->option->updateOrCreate(['option_name' => $name], $updated_options)) {
            $this->flushCache();
            return TRUE;
        }
        return FALSE;
    }

}
