<?php

namespace Polyether\Option;

use Cache;
use Illuminate\Database\Eloquent\Collection;
use Polyether\Option\Repositories\OptionRepository;

class OptionAPI
{

    protected $option;
    protected $autoload = [];
    protected $cached = [];
    protected $notoption = [];

    public function __construct(OptionRepository $option)
    {
        $this->option = $option;
        $this->autoloadOptions();
    }

    public function autoloadOptions()
    {
        $cacheKey = 'options_autoload';

        if (Cache::tags('options')->has($cacheKey)) {
            $this->autoload = Cache::get($cacheKey);
        } else {
            $options = $this->option->findWhere(['autoload' => 'yes'], ['option_name', 'option_value']);

            if ($options instanceof Collection) {
                foreach ($options as $option) {
                    $this->autoload[ $option->option_name ] = $option->option_value;
                }
                Cache::tags('options')->put($cacheKey, $this->autoload, $this->get('options_cache_expires', 60));
            }
        }
    }

    public function get($name, $default = false)
    {
        $cacheKey = 'option_' . md5($name);

        if (isset($this->notoption[ $name ])) {
            if ($default) {
                return $default;
            }

            return false;
        }


        if (isset($this->autoload[ $name ])) {
            $value = $this->autoload[ $name ];
        } else if (false !== ($cachedOption = $this->getCached($name))) {
            $value = $cachedOption;
        } else if (Cache::tags('options')->has($cacheKey)) {
            $value = Cache::tags('options')->get($cacheKey);
        } else {
            $query = $this->option->findBy('option_name', $name);

            if ($query) {
                $value = $query->option_value;
                $this->cache($name, $value);
                Cache::tags('options')->put($cacheKey, $value, $this->get('options_cache_expires', 60));
            } else {
                $this->notoption[ $name ] = true;
                if ($default) {
                    return $default;
                }

                return false;
            }
        }

        return unjsonizeMaybe($value);
    }

    protected function getCached($name)
    {
        return isset($this->cached[ $name ]) ? $this->cached[ $name ] : false;
    }

    protected function cache($name, $value)
    {
        $this->cached[ $name ] = $value;
    }

    public function set($name, $value, $autoload = 'yes')
    {

        if (true === $autoload) {
            $autoload = 'yes';
        }

        if (false === $autoload) {
            $autoload = 'no';
        }

        $value = jsonizeMaybe($value);

        if ($this->option->create(['option_name' => $name, 'option_value' => $value, 'autoload' => $autoload])) {

            if ('yes' === $autoload) {
                Cache::tags('options')->forget('options_autoload');
            }

            return true;
        }

        return false;
    }

    public function update($name, $value, $autoload = null)
    {

        if (true === $autoload) {
            $autoload = 'yes';
        }

        if (false === $autoload) {
            $autoload = 'no';
        }

        $value = jsonizeMaybe($value);
        $updated_options = ['option_name' => $name, 'option_value' => $value];

        if ($autoload !== null) {
            $updated_options[ 'autoload' ] = $autoload;
        }

        if ($this->option->updateOrCreate(['option_name' => $name], $updated_options)) {
            Cache::tags('options')->forget('option_' . md5($name));

            return true;
        }

        return false;
    }

}
