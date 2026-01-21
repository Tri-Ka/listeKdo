<?php

class Reaction
{
    public ?int $id = null;
    public ?string $product_id = null;
    public ?string $user_id = null;
    public ?string $type = null;
    public ?string $created_at = null;

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

        if (array_key_exists('product_id', $data)) {
            $this->product_id = null === $data['product_id'] ? null : (string) $data['product_id'];
        }

        if (array_key_exists('user_id', $data)) {
            $this->user_id = null === $data['user_id'] ? null : (string) $data['user_id'];
        }

        if (array_key_exists('type', $data)) {
            $this->type = null === $data['type'] ? null : (string) $data['type'];
        }

        if (array_key_exists('created_at', $data)) {
            $this->created_at = null === $data['created_at'] ? null : (string) $data['created_at'];
        }
    }

    public function toArray(): array
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
