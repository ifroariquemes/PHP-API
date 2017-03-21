<?php

namespace PhpApiTests;

use PhpApi\Annotations\Api;
use PhpApi\Annotations\HttpMethod;

class Contacts
{

    use \PhpApi\ApiTrait;

    /**
     * @Api("contacts/new")
     */
    public function newContact()
    {
        echo 'new';
    }

}
