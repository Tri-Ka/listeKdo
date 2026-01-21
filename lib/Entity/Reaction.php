<?php

class Reaction
{
    var $id;
    var $product_id;
    var $user_id;
    var $type;
    var $created_at;

    function Reaction($data)
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

        if (isset($data['product_id'])) {
            $this->product_id = $data['product_id'];
        }

        if (isset($data['user_id'])) {
            $this->user_id = $data['user_id'];
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
            'product_id' => $this->product_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'created_at' => $this->created_at,
        );
    }
}
