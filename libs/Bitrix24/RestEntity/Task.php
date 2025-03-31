<?php

namespace Lib\Bitrix24\RestEntity;

use Lib\bitrix24\ApiClient;

class Task implements IRestEntity
{
    public ApiClient $b24API;
    protected int $entityTypeId;

    public function __construct(ApiClient $b24API)
    {
        $this->b24API = $b24API;
    }

    public function add(array $fields): array
    {
        return $this->b24API->call('tasks.task.add', [
            'fields' => $fields,
        ]);
    }

    public function get(int $id): array
    {
        return $this->b24API->call('tasks.task.get', [
            'id' => $id,
        ]);
    }

    public function list(array $filter, array $select = [], array $order = [], int $start = 0): array
    {
        return $this->b24API->call('tasks.task.list', [
            'filter' => $filter,
            'select' => $select ?: ['*'],
            'order' => $order,
            'start' => $start ?: 0,
        ]);
    }

    public function update(int $id, array $fields): array
    {
        return $this->b24API->call('tasks.task.update', [
            'taskId' => $id,
            'fields' => $fields,
        ]);
    }

    public function delete(int $id): array
    {
        return $this->b24API->call('tasks.task.delete', [
            'id' => $id
        ]);
    }
}