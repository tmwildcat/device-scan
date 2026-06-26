<?php

namespace App\DeviceScan\Metadata;

class CandidateValue
{
    public function __construct(
        /**
         * Canonical engineering field
         * Example: Voc, Vmp, PNom, NbMPPT
         */
        public readonly string $field,

        /**
         * Parsed engineering value
         */
        public readonly mixed $value,

        /**
         * Engineering unit
         * Example: V, A, W, Wp, %, kg
         */
        public readonly ?string $unit = null,

        /**
         * Confidence (0.0 - 1.0)
         * Null = not evaluated
         */
        public readonly ?float $confidence = null,

        /**
         * Dictionary alias that matched
         * Example: Open Circuit Voltage
         */
        public readonly ?string $matchedAlias = null,

        /**
         * Regex or matcher responsible
         */
        public readonly ?string $matchedPattern = null,

        /**
         * Original text from datasheet
         */
        public readonly ?string $rawText = null,

        /**
         * Source location
         */
        public readonly ?int $page = null,

        public readonly ?int $line = null,

        public readonly ?int $characterOffset = null,

        /**
         * PDF/OCR coordinates (future)
         */
        public readonly ?array $boundingBox = null,

        /**
         * pdf-text
         * ocr
         * ai
         * manual
         */
        public readonly ?string $source = null,

        /**
         * Engineering validation
         */
        public readonly bool $isValid = true,

        public readonly ?string $validationMessage = null,

        /**
         * Extensible metadata
         */
        public readonly array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'value' => $this->value,
            'unit' => $this->unit,
            'confidence' => $this->confidence,
            'matched_alias' => $this->matchedAlias,
            'matched_pattern' => $this->matchedPattern,
            'raw_text' => $this->rawText,
            'page' => $this->page,
            'line' => $this->line,
            'character_offset' => $this->characterOffset,
            'bounding_box' => $this->boundingBox,
            'source' => $this->source,
            'is_valid' => $this->isValid,
            'validation_message' => $this->validationMessage,
            'metadata' => $this->metadata,
        ];
    }
}