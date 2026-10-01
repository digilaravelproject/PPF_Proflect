<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use ZipArchive;

class WarrantyCodeSheetReader
{
    private const SPREADSHEET_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const MAX_CODES = 5000;

    private const MAX_XML_BYTES = 5_000_000;

    public function read(string $path): array
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw new InvalidArgumentException('The uploaded file is not a readable Excel workbook.');
        }

        try {
            $sharedStrings = $this->sharedStrings($archive);
            $sheetPath = $this->firstWorksheetPath($archive);
            $sheetXml = $this->entry($archive, $sheetPath, self::MAX_XML_BYTES);
            $codes = $this->codesFromWorksheet($sheetXml, $sharedStrings);
        } finally {
            $archive->close();
        }

        return $codes;
    }

    private function sharedStrings(ZipArchive $archive): array
    {
        if ($archive->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        [, $xpath] = $this->xml($this->entry($archive, 'xl/sharedStrings.xml', 1_000_000));
        $strings = [];
        foreach ($xpath->query('//x:si') as $item) {
            $text = '';
            foreach ($xpath->query('.//x:t', $item) as $node) {
                $text .= $node->textContent;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function firstWorksheetPath(ZipArchive $archive): string
    {
        [, $workbookXPath] = $this->xml($this->entry($archive, 'xl/workbook.xml', 500_000));
        $sheet = $workbookXPath->query('//x:sheets/x:sheet')->item(0);
        if (! $sheet instanceof DOMElement) {
            throw new InvalidArgumentException('The workbook does not contain a worksheet.');
        }

        $relationshipId = $sheet->getAttributeNS(self::OFFICE_REL_NS, 'id');
        [, $relationshipsXPath] = $this->xml($this->entry($archive, 'xl/_rels/workbook.xml.rels', 500_000), self::PACKAGE_REL_NS);
        $target = null;
        foreach ($relationshipsXPath->query('//x:Relationship') as $relationship) {
            if ($relationship instanceof DOMElement && $relationship->getAttribute('Id') === $relationshipId) {
                $target = $relationship->getAttribute('Target');
                break;
            }
        }

        $path = 'xl/'.ltrim((string) $target, '/');
        $path = str_replace('xl/xl/', 'xl/', $path);
        if (! $target || str_contains($path, '..') || ! str_starts_with($path, 'xl/worksheets/')) {
            throw new InvalidArgumentException('The workbook worksheet could not be read.');
        }

        return $path;
    }

    private function codesFromWorksheet(string $xml, array $sharedStrings): array
    {
        [, $xpath] = $this->xml($xml);
        $values = [];
        $headerFound = false;

        foreach ($xpath->query('//x:sheetData/x:row/x:c') as $cell) {
            if (! $cell instanceof DOMElement || ! preg_match('/^A(\d+)$/i', $cell->getAttribute('r'), $match)) {
                continue;
            }

            $row = (int) $match[1];
            $value = trim($this->cellValue($xpath, $cell, $sharedStrings));
            if ($row === 1) {
                $header = strtolower(preg_replace('/\s+/', ' ', $value));
                if ($header !== 'warranty code') {
                    throw new InvalidArgumentException('Cell A1 must contain the heading "Warranty Code".');
                }

                $headerFound = true;

                continue;
            }

            if ($value === '') {
                continue;
            }

            if (preg_match('/^\d+\.0+$/', $value)) {
                $value = strstr($value, '.', true);
            }
            if (! preg_match('/^\d{1,5}$/', $value)) {
                throw new InvalidArgumentException("Row {$row} must contain a five-digit warranty code.");
            }

            $code = str_pad($value, 5, '0', STR_PAD_LEFT);
            if (isset($values[$code])) {
                throw new InvalidArgumentException("Warranty code {$code} is repeated in the uploaded sheet.");
            }
            $values[$code] = true;

            if (count($values) > self::MAX_CODES) {
                throw new InvalidArgumentException('A warranty code sheet can contain at most '.self::MAX_CODES.' codes.');
            }
        }

        if (! $headerFound) {
            throw new InvalidArgumentException('Cell A1 must contain the heading "Warranty Code".');
        }
        if ($values === []) {
            throw new InvalidArgumentException('Add at least one warranty code below the heading in column A.');
        }

        return array_keys($values);
    }

    private function cellValue(DOMXPath $xpath, DOMElement $cell, array $sharedStrings): string
    {
        $type = $cell->getAttribute('t');
        if ($type === 'inlineStr') {
            $text = '';
            foreach ($xpath->query('.//x:is//x:t', $cell) as $node) {
                $text .= $node->textContent;
            }

            return $text;
        }

        $node = $xpath->query('./x:v', $cell)->item(0);
        $value = $node?->textContent ?? '';

        return $type === 's' ? (string) ($sharedStrings[(int) $value] ?? '') : $value;
    }

    private function entry(ZipArchive $archive, string $name, int $maxBytes): string
    {
        $index = $archive->locateName($name);
        $size = $index === false ? false : ($archive->statIndex($index)['size'] ?? false);
        if ($index === false || $size === false || $size > $maxBytes) {
            throw new InvalidArgumentException('The uploaded workbook is missing required data or is too large.');
        }

        $contents = $archive->getFromIndex($index);
        if ($contents === false) {
            throw new InvalidArgumentException('The uploaded workbook could not be read.');
        }

        return $contents;
    }

    private function xml(string $xml, string $namespace = self::SPREADSHEET_NS): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            throw new InvalidArgumentException('The uploaded workbook contains invalid XML.');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('x', $namespace);

        return [$document, $xpath];
    }
}
