<?php

class Gift
{
    var $id;
    var $user_id;
    var $nom;
    var $description;
    var $image_url;
    var $link;
    var $file;
    var $gifted_by;
    var $created_at;
    var $updated_at;

    function Gift($data)
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

        if (isset($data['user_id'])) {
            $this->user_id = $data['user_id'];
        }

        if (isset($data['nom'])) {
            $this->nom = $data['nom'];
        }

        if (isset($data['description'])) {
            $this->description = $data['description'];
        }

        if (isset($data['image_url'])) {
            $this->image_url = $data['image_url'];
        }

        if (isset($data['link'])) {
            $this->link = $data['link'];
        }

        if (isset($data['file'])) {
            $this->file = $data['file'];
        }

        if (isset($data['gifted_by'])) {
            $this->gifted_by = $data['gifted_by'];
        }

        if (isset($data['created_at'])) {
            $this->created_at = $data['created_at'];
        }

        if (isset($data['updated_at'])) {
            $this->updated_at = $data['updated_at'];
        }
    }

    function toArray()
    {
        return array(
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nom' => $this->nom,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'link' => $this->link,
            'file' => $this->file,
            'gifted_by' => $this->gifted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        );
    }
}
