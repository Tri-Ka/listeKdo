<?php

class Comment
{
    var $id;
    var $content;
    var $product_id;
    var $user_id;
    var $created_at;

    function Comment($data)
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

        if (isset($data['content'])) {
            $this->content = $data['content'];
        }

        if (isset($data['product_id'])) {
            $this->product_id = $data['product_id'];
        }

        if (isset($data['user_id'])) {
            $this->user_id = $data['user_id'];
        }

        if (isset($data['created_at'])) {
            $this->created_at = $data['created_at'];
        }
    }

    function toArray()
    {
        return array(
            'id' => $this->id,
            'content' => $this->content,
            'product_id' => $this->product_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
        );
    }
}
