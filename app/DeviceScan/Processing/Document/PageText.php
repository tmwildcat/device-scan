<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Document;

use App\DeviceScan\Processing\Native\DTO\NativeWord;

final readonly class PageText
{
    /**
     * @param NativeWord[]|array[] $words
     */
    public function __construct(
        public string $content,
        public bool $ocr = false,
        public array $words = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'ocr' => $this->ocr,
            'words' => array_map(
                fn (NativeWord|array $word) => $word instanceof NativeWord
                    ? $word->toArray()
                    : $word,
                $this->words,
            ),
            'metadata' => $this->metadata,
        ];
    }
}