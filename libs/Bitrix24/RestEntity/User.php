<?php

namespace Lib\Bitrix24\RestEntity;

use Lib\bitrix24\ApiClient;

class User implements IRestEntity
{
    public ApiClient $b24API;
    protected int $entityTypeId;

    public function __construct(ApiClient $b24API)
    {
        $this->b24API = $b24API;
    }

    public function add(array $fields): array
    {
        return $this->b24API->call('user.add', $fields);
    }

    public function get(int $id): array
    {
        return $this->b24API->call('user.get', [
            'FILTER' => [
                'id' => $id,
            ]
        ]);
    }

    public function list(array $filter, array $select = [], array $order = [], int $start = 0): array
    {
        return $this->b24API->call('user.get', [
            'FILTER' => $filter,
            'select' => $select ?: ['*'],
            'order' => $order,
            'start' => $start ?: 0,
        ]);
    }

    public function update(int $id, array $fields): array
    {
        return $this->b24API->call('user.update', ['ID' => $id, ...$fields]);
    }

    public function delete(int $id): array
    {
        return [
            'error' => 'method_error',
            'error_information' => 'Unable to delete user',
        ];
    }

    public function getCurrent()
    {
        return $this->b24API->call('user.current');
    }
}