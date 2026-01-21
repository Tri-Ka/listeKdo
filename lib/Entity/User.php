<?php

class User
{
    public ?int $id = null;
    public ?string $nom = null;
    public ?string $code = null;
    public ?string $password = null;
    public ?string $theme = null;
    public ?string $pictureFile = null;
    public ?string $pictureFileUrl = null;
    public ?string $googleId = null;
    public ?string $last_seen_notif = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct($data = null)
    {
        $this->hydrate($data);
    }

    public function hydrate($data): void
    {
        if (!is_array($data)) {
            return;
        }

        if (array_key_exists('id', $data)) {
            $this->id = null === $data['id'] ? null : (int) $data['id'];
        }

        if (array_key_exists('nom', $data)) {
            $this->nom = null === $data['nom'] ? null : (string) $data['nom'];
        }

        if (array_key_exists('code', $data)) {
            $this->code = null === $data['code'] ? null : (string) $data['code'];
        }

        if (array_key_exists('password', $data)) {
            $this->password = null === $data['password'] ? null : (string) $data['password'];
        }

        if (array_key_exists('theme', $data)) {
            $this->theme = null === $data['theme'] ? null : (string) $data['theme'];
        }

        if (array_key_exists('pictureFile', $data)) {
            $this->pictureFile = null === $data['pictureFile'] ? null : (string) $data['pictureFile'];
        }

        if (array_key_exists('pictureFileUrl', $data)) {
            $this->pictureFileUrl = null === $data['pictureFileUrl'] ? null : (string) $data['pictureFileUrl'];
        }

        if (array_key_exists('googleId', $data)) {
            $this->googleId = null === $data['googleId'] ? null : (string) $data['googleId'];
        }

        if (array_key_exists('last_seen_notif', $data)) {
            $this->last_seen_notif = null === $data['last_seen_notif'] ? null : (string) $data['last_seen_notif'];
        }

        if (array_key_exists('created_at', $data)) {
            $this->created_at = null === $data['created_at'] ? null : (string) $data['created_at'];
        }

        if (array_key_exists('updated_at', $data)) {
            $this->updated_at = null === $data['updated_at'] ? null : (string) $data['updated_at'];
        }
    }

    public function toArray(): array
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
