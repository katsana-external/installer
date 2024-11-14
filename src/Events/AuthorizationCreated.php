<?php

namespace Orchestra\Installation\Events;

use Orchestra\Contracts\Authorization\Authorization;

class AuthorizationCreated extends Event
{
    /**
     * The ACL instance.
     *
     * @var \Orchestra\Contracts\Authorization\Authorization
     */
    public $acl;

    /**
     * Create a new event instance.
     */
    public function __construct($acl)
    {
        $this->acl = $acl;
    }
}
