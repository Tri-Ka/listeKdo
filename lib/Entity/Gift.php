<?php

class Gift
{
    public ?int $id = null;
    public ?string $user_id = null;
    public ?string $nom = null;
    public ?string $description = null;
    public ?string $image_url = null;
    public ?string $link = null;
    public ?string $file = null;
    public ?string $gifted_by = null;
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

        if (array_key_exists('user_id', $data)) {
            $this->user_id = null === $data['user_id'] ? null : (string) $data['user_id'];
        }

        if (array_key_exists('nom', $data)) {
            $this->nom = null === $data['nom'] ? null : (string) $data['nom'];
        }

        if (array_key_exists('description', $data)) {
            $this->description = null === $data['description'] ? null : (string) $data['description'];
        }

        if (array_key_exists('image_url', $data)) {
            $this->image_url = null === $data['image_url'] ? null : (string) $data['image_url'];
        }

        if (array_key_exists('link', $data)) {
            $this->link = null === $data['link'] ? null : (string) $data['link'];
        }

        if (array_key_exists('file', $data)) {
            $this->file = null === $data['file'] ? null : (string) $data['file'];
        }

        if (array_key_exists('gifted_by', $data)) {
            $this->gifted_by = null === $data['gifted_by'] ? null : (string) $data['gifted_by'];
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
