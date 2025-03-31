<?php

namespace Lib\Bitrix24\RestEntity;

use Lib\bitrix24\ApiClient;

class CRM implements IRestEntity
{
    const ENTITY_TYPE_IDS = [
        'lead' => 1,
        'deeal' => 2,
        'contact' => 3,
        'company' => 4,
        'quote' => 7,
        'invoice' => 31,
    ];

    public ApiClient $b24API;
    protected int $entityTypeId;

    /**
     * @param ApiClient $b24API
     * @param string $entityTpe Возможные значения можно получить через метод getEntityTypes
     */
    public function __construct(ApiClient $b24API, string $entityTpe)
    {
        $this->b24API = $b24API;
        $this->entityTypeId = \key_exists($entityTpe, static::ENTITY_TYPE_IDS) ? static::ENTITY_TYPE_IDS[$entityTpe] : $entityTpe;
    }

    public function getEntityTypes(): array
    {
        return array_keys(static::ENTITY_TYPE_IDS);
    }

    public function add(array $fields): array
    {
        return $this->b24API->call('crm.item.add', [
            'entityTypeId' =>  $this->entityTypeId,
            'fields' => $fields,
            'useOriginalUfNames' => 'Y'
        ]);
    }

    public function get(int $id): array
    {
        return $this->b24API->call('crm.item.get', [
            'entityTypeId' =>  $this->entityTypeId,
            'id' => $id,
            'useOriginalUfNames' => 'Y'
        ]);
    }

    public function list(array $filter, array $select = [], array $order = [], int $start = 0): array
    {
        return $this->b24API->call('crm.item.list', [
            'entityTypeId' =>  $this->entityTypeId,
            'filter' => $filter,
            'select' => $select ?: ['*'],
            'order' => $order,
            'start' => $start ?: 0,
            'useOriginalUfNames' => 'Y'
        ]);
    }

    public function update(int $id, array $fields): array
    {
        return $this->b24API->call('crm.item.update', [
            'entityTypeId' =>  $this->entityTypeId,
            'id' => $id,
            'fields' => $fields,
            'useOriginalUfNames' => 'Y'
        ]);
    }

    public function delete(int $id): array
    {
        return $this->b24API->call('crm.item.delete', [
            'entityTypeId' =>  $this->entityTypeId,
            'id' => $id
        ]);
    }
}