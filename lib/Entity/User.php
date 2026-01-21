<?php

class User
{
    var $id;
    var $nom;
    var $code;
    var $password;
    var $theme;
    var $pictureFile;
    var $pictureFileUrl;
    var $googleId;
    var $last_seen_notif;
    var $created_at;
    var $updated_at;

    function User($data)
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

        if (isset($data['nom'])) {
            $this->nom = $data['nom'];
        }

        if (isset($data['code'])) {
            $this->code = $data['code'];
        }

        if (isset($data['password'])) {
            $this->password = $data['password'];
        }

        if (isset($data['theme'])) {
            $this->theme = $data['theme'];
        }

        if (isset($data['pictureFile'])) {
            $this->pictureFile = $data['pictureFile'];
        }

        if (isset($data['pictureFileUrl'])) {
            $this->pictureFileUrl = $data['pictureFileUrl'];
        }

        if (isset($data['googleId'])) {
            $this->googleId = $data['googleId'];
        }

        if (isset($data['last_seen_notif'])) {
            $this->last_seen_notif = $data['last_seen_notif'];
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
            'nom' => $this->nom,
            'code' => $this->code,
            'password' => $this->password,
            'theme' => $this->theme,
            'pictureFile' => $this->pictureFile,
            'pictureFileUrl' => $this->pictureFileUrl,
            'googleId' => $this->googleId,
            'last_seen_notif' => $this->last_seen_notif,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        );
    }
}
