<?php

namespace PhpApiTests;

use PhpApi\Annotations\Api;
use PhpApi\Annotations\HttpMethod;

class Blog
{

    use \PhpApi\ApiTrait;

    /**
     * @Api("post/new")
     * @HttpMethod("GET")
     */
    public function newPost()
    {
        $this->response('new');
    }

    /**
     * @Api("post/new")
     * @HttpMethod("POST,DELETE")
     */
    public function saveNewPost()
    {
        $this->response('save');
    }

    /**
     * @Api("post/$id/edit")
     */
    public function editPost($id)
    {
        $this->response($id);
    }

}
