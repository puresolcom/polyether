<?php

namespace Polyether\Option;

use Illuminate\Database\Eloquent\Collection;
use Polyether\Option\Repositories\OptionRepository;

class OptionAPI
{

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
        $options = $this->option->findWhere(['autoload' => 'yes'], ['option_name', 'option_value']);

        if ($options instanceof Collection) {
            foreach ($options as $option) {
                $this->autoload[ $option->option_name ] = $option->option_value;
            }
        }
    }

    public function get ($name)
    {

        if (isset($this->notoption[ $name ])) {
            return false;
        }

        if (isset($this->autoload[ $name ])) {
            $value = $this->autoload[ $name ];
        } else if (FALSE !== ($cachedOption = $this->getCached($name))) {
            $value = $cachedOption;
        } else {
            $query = $this->option->findBy('option_name', $name);

            if ($query) {
                $value = $query->option_value;
                $this->cache($name, $value);
            } else {
                $this->notoption[ $name ] = true;
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

        if ($this->option->create(['option_name' => $name, 'option_value' => $value, 'autoload' => $autoload]))
            return TRUE;
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

        if ($this->option->updateOrCreate(['option_name' => $name], $updated_options))
            return TRUE;
        return FALSE;
    }

}
