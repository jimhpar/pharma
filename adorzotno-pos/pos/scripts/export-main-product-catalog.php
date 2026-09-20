<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = $argv[1] ?? 'odorzotnopos';
$outputPath = $argv[2] ?? storage_path('app/exports/main-product-catalog-' . date('Y-m-d') . '.xlsx');

config(['database.connections.mysql.database' => $database]);
DB::purge('mysql');
$connection = DB::connection('mysql');

$rows = $connection->table('products as p')
    ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
    ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
    ->orderBy('p.id')
    ->select([
        'p.name as product_name',
        'p.generic_name',
        'c.name as category',
        'b.name as brand',
    ])
    ->cursor();

$directory = dirname($outputPath);
if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
    throw new RuntimeException("Unable to create export directory: {$directory}");
}

$sheetTempPath = $outputPath . '.sheet.xml';
$sheet = fopen($sheetTempPath, 'wb');
if ($sheet === false) {
    throw new RuntimeException("Unable to create temporary worksheet: {$sheetTempPath}");
}

$headers = ['Product Name', 'Generic Name', 'Category', 'Brand'];
$rowCount = 1;

fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">');
fwrite($sheet, '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');
fwrite($sheet, '<cols><col min="1" max="1" width="42" customWidth="1"/><col min="2" max="2" width="34" customWidth="1"/><col min="3" max="3" width="32" customWidth="1"/><col min="4" max="4" width="38" customWidth="1"/></cols>');
fwrite($sheet, '<sheetData>');
writeRow($sheet, $rowCount, $headers, 1);

foreach ($rows as $row) {
    ++$rowCount;
    writeRow($sheet, $rowCount, [
        $row->product_name,
        $row->generic_name,
        $row->category,
        $row->brand,
    ]);
}

fwrite($sheet, '</sheetData><autoFilter ref="A1:D' . $rowCount . '"/></worksheet>');
fclose($sheet);

$zip = new ZipArchive();
if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    @unlink($sheetTempPath);
    throw new RuntimeException("Unable to create workbook: {$outputPath}");
}

$zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML);
$zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
$zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets><sheet name="Products" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML);
$zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML);
$zip->addFromString('xl/styles.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font></fonts>
    <fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill></fills>
    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>
</styleSheet>
XML);
$zip->addFile($sheetTempPath, 'xl/worksheets/sheet1.xml');
$zip->close();
unlink($sheetTempPath);

echo "Database: {$database}\n";
echo 'Products exported: ' . number_format($rowCount - 1) . "\n";
echo "File: {$outputPath}\n";

function writeRow($sheet, int $rowNumber, array $values, int $style = 0): void
{
    fwrite($sheet, '<row r="' . $rowNumber . '">');

    foreach ($values as $index => $value) {
        $cell = columnName($index + 1) . $rowNumber;
        $escaped = htmlspecialchars(cleanXmlText((string) ($value ?? '')), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        fwrite($sheet, '<c r="' . $cell . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t xml:space="preserve">' . $escaped . '</t></is></c>');
    }

    fwrite($sheet, '</row>');
}

function columnName(int $column): string
{
    $name = '';

    while ($column > 0) {
        $column--;
        $name = chr(65 + ($column % 26)) . $name;
        $column = intdiv($column, 26);
    }

    return $name;
}

function cleanXmlText(string $value): string
{
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';
}
