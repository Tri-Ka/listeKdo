<?php

class Notification
{
    var $id;
    var $author_id;
    var $product_id;
    var $type;
    var $created_at;

    function Notification($data)
    {
        $this->hydrate($data);
    }

    function hydrate($data)
    {
        if (!$data) {
            return;
        }

        if (isset($data['id'])) {
            $this->id = $data['id'];
        }

        if (isset($data['author_id'])) {
            $this->author_id = $data['author_id'];
        }

        if (isset($data['product_id'])) {
            $this->product_id = $data['product_id'];
        }

        if (isset($data['type'])) {
            $this->type = $data['type'];
        }

        if (isset($data['created_at'])) {
            $this->created_at = $data['created_at'];
        }
    }

    function toArray()
    {
        return array(
            'id' => $this->id,
            'author_id' => $this->author_id,
            'product_id' => $this->product_id,
            'type' => $this->type,
            'created_at' => $this->created_at,
        );
    }
}
