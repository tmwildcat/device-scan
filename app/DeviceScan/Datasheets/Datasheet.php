<?php

declare(strict_types=1);

namespace App\DeviceScan\Datasheets;

final readonly class Datasheet
{
    /**
     * @param DatasheetModelGroup[] $modelGroups
     */
    public function __construct(
        public string $deviceType,
        public ?string $manufacturer = null,
        public ?string $title = null,
        public ?string $documentNumber = null,
        public ?string $revision = null,
        public ?string $version = null,
        public ?string $language = null,
        public ?int $pageCount = null,
        public string $status = 'parsed',
        public array $modelGroups = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'device_type' => $this->deviceType,
            'manufacturer' => $this->manufacturer,
            'title' => $this->title,
            'document_number' => $this->documentNumber,
            'revision' => $this->revision,
            'version' => $this->version,
            'language' => $this->language,
            'page_count' => $this->pageCount,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'model_groups' => array_map(
                fn (DatasheetModelGroup $group) => $group->toArray(),
                $this->modelGroups
            ),
        ];
    }
}