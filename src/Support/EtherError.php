<?php

namespace Polyether\Support;

use Illuminate\Support\MessageBag;

class EtherError extends MessageBag
{

    public function __construct ($messages = array())
    {
        if ($messages instanceof \Exception) {
            $messages = $messages->getMessage() . ' on file ' . $messages->getFile() . ' line ('.$messages->getLine().')';
        }
        if (is_string($messages))
            $messages = (array)$messages;
        parent::__construct($messages);
    }

}
