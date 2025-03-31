<?php

namespace Lib\Bitrix24\RestControlleer;

interface IRestEntity
{
    public function add(array $fields): array;
    public function get(int $id): array;
    public function list(array $filter, array $select = [], array $order = [], int $start = 0): array;
    public function update(int $id, array $fields): array;
    public function delete(int $id): array;
}