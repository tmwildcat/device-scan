<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

use App\DeviceScan\Processing\Ocr\OcrResult;

final class ModuleElectricalProcessor
{
    public function __construct(
        private readonly ModuleElectricalCharacteristicsParser $parser,
    ) {}

    public function extract(OcrResult $ocr): ?CanonicalModuleElectricalCharacteristics
    {
        foreach ($ocr->engineeringTables as $table) {
            $result = $this->parser->parse($table);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}