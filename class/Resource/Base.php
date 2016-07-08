<?php

/**
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */

namespace properties\Resource;

class Base extends \phpws2\Resource
{

    public function __set($name, $value)
    {
        if (!$this->$name->allowNull() && 
                ( (is_string($value) && $value === '') || is_null($value))) {
            throw new \properties\Exception\MissingInput("$name may not be empty");
        }

        $this->$name->set($value);
    }

    public function __get($name)
    {
        $method_name = self::walkingCase($name, 'get');
        if (method_exists($this, $method_name)) {
            return $this->$method_name();
        } else {
            return $this->$name->get();
        }
    }

}
