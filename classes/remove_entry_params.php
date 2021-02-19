<?php

namespace block_user_manager;

class remove_entry_params
{
    public $id;
    public $url;

    public function __construct($id, $url) {
        $this->id = $id;
        $this->url = $url;
    }
}