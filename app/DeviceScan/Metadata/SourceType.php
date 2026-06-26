<?php

namespace App\DeviceScan\Metadata;

enum SourceType: string
{
    case PdfText = 'pdf-text';

    case Ocr = 'ocr';

    case Ai = 'ai';

    case Manual = 'manual';

    case Dictionary = 'dictionary';
}