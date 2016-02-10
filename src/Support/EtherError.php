<?php

namespace Polyether\Support;

use Illuminate\Support\MessageBag;

class EtherError extends MessageBag
{

    public function __construct ($messages = array())
    {
        if (is_string($messages))
            $messages = (array)$messages;
        parent::__construct($messages);
    }

}
