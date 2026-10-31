<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Allow logged in students, teachers, or admins
if (!is_logged_in()) {
    header('Location: login.php?msg=auth_required');
    exit;
}

$lsCode = strtoupper(trim((string)($_GET['ls'] ?? 'LS1')));
$modulesMeta = [
    'LS1' => [
        'title' => 'Learning Strand 1: Communication Skills (Filipino and English)',
        'subtitle' => 'Pakikipagtalastasan sa Filipino at Ingles',
        'desc' => 'Modyul para sa pagbasa nang may pag-unawa, pagsulat ng sanaysay at pormal na liham, pakikinig, at mabisang pakikipagtalastasan sa trabaho at komunidad.',
        'competencies' => [
            'Pagpapalawak ng Talasalitaan at Pag-unawa sa Binasa (Reading Comprehension)',
            'Pagsulat ng Liham Pangnegosyo, Aplikasyon sa Trabaho, at Pormal na Ulat',
            'Mabisang Pagsasalita at Pakikipagtalastasan sa Pamilya at Pamayanan'
        ],
        'lessons' => [
            'Aralin 1: Ang Wastong Balarila at Pagbuo ng Mabisang Pangungusap',
            'Aralin 2: Pagsusuri ng mga Tekstong Impormatibo at Sanaysay',
            'Aralin 3: Business Correspondence and Job Application Strategies'
        ]
    ],
    'LS2' => [
        'title' => 'Learning Strand 2: Scientific Literacy & Critical Thinking Skills',
        'subtitle' => 'Agham at Siyentipikong Pag-iisip',
        'desc' => 'Paggamit ng siyentipikong pamamaraan sa paggawa ng desisyon, pangangalaga sa kalikasan, kalusugan, nutrisyon, at pag-iwas sa karamdaman.',
        'competencies' => [
            'Siyentipikong Pamamaraan (Scientific Method) sa Paglutas ng Suliranin',
            'Ekolohikal na Kamalayan, Climate Change, at Tamang Waste Management',
            'Wastong Nutrisyon, Kalinisan sa Katawan, at Pangunang Lunas (First Aid)'
        ],
        'lessons' => [
            'Aralin 1: Siyentipikong Pagmamasid at Pagsusuri ng Ebidensya',
            'Aralin 2: Ekolohiya at Pangangalaga ng Likas-Yaman sa Barangay',
            'Aralin 3: Kalusugan ng Pamilya at Pamamahala ng Sakit'
        ]
    ],
    'LS3' => [
        'title' => 'Learning Strand 3: Mathematical & Problem Solving Skills',
        'subtitle' => 'Praktikal na Matematika sa Pang-araw-araw',
        'desc' => 'Pag-aaral ng basic numeracy, fractions, ratio at proportion, percentages, pagsusuri ng datos, at financial literacy sa negosyo at tahanan.',
        'competencies' => [
            'Pangunahing Operasyon sa Matematika, Fractions, at Decimals',
            'Porsyento, Diskwento, Interes sa Pautang, at Pagbabadget sa Tahanan',
            'Pagsukat ng Lawak, Timbang, Temperatura, at Pagbasa ng Graph'
        ],
        'lessons' => [
            'Aralin 1: Praktikal na Pagkukuwenta ng Kita, Gastos, at Ipon',
            'Aralin 2: Porsyento, Diskwento, at Simple Interest sa Pamilihan',
            'Aralin 3: Basic Statistics and Interpreting Community Data'
        ]
    ],
    'LS4' => [
        'title' => 'Learning Strand 4: Life & Career Skills',
        'subtitle' => 'Kasanayan sa Hanapbuhay at Negosyo',
        'desc' => 'Paghahanda para sa hanapbuhay, TVET courses, micro-entrepreneurship, work ethics, at kaligtasan sa lugar ng paggawa.',
        'competencies' => [
            'Pagpaplano ng Karera at Pagtukoy sa Sariling Kasanayan sa Trabaho',
            'Pagsisimula at Pamamahala ng Maliit na Negosyo (Entrepreneurship)',
            'Work Ethics, Karapatan ng Manggagawa, at Occupational Safety (OSH)'
        ],
        'lessons' => [
            'Aralin 1: Pagtukoy sa Sariling Kakayahan at Career Assessment',
            'Aralin 2: Pagbuo ng Simpleng Business Plan at Pagsukat ng Tubo',
            'Aralin 3: Kaligtasan sa Trabaho at Karapatan ng mga Manggagawa'
        ]
    ],
    'LS5' => [
        'title' => 'Learning Strand 5: Understanding the Self & Society',
        'subtitle' => 'Komunidad at Responsableng Pagkamamamayan',
        'desc' => 'Pag-unawa sa sarili, emosyonal na kalusugan, kasaysayan ng Pilipinas, karapatang pantao, batas, at aktibong pakikilahok sa komunidad.',
        'competencies' => [
            'Pagpapahalaga sa Sarili, Emosyonal na Katatagan, at Pamilya',
            'Kamalayang Pangkasaysayan at Pagpapahalaga sa Kulturang Pilipino',
            'Karapatang Pantao, Kapayapaan, at Pagsunod sa mga Batas'
        ],
        'lessons' => [
            'Aralin 1: Ang Aking Tungkulin sa Aking Sarili at Pamilya',
            'Aralin 2: Karapatang Pantao at Responsableng Pagsunod sa Batas',
            'Aralin 3: Pakikilahok sa mga Proyekto at Serbisyo sa Barangay'
        ]
    ],
    'LS6' => [
        'title' => 'Learning Strand 6: Digital Literacy (Kasanayang Digital)',
        'subtitle' => 'Paggamit ng Computer at Internet',
        'desc' => 'Paggamit ng computer at smartphone, paggawa ng dokumento at spreadsheet, ligtas na pag-surf sa internet, at cyber hygiene.',
        'competencies' => [
            'Pangunahing Bahagi at Paggamit ng Computer at Smartphone',
            'Paggawa ng Dokumento (Word Processor) at Simple Spreadsheets',
            'Ligtas at Responsableng Paggamit ng Internet, Email, at Social Media'
        ],
        'lessons' => [
            'Aralin 1: Introduksyon sa Computer Hardware, OS, at File Management',
            'Aralin 2: Paggawa ng Dokumento at Resume gamit ang Word Processor',
            'Aralin 3: Cyber Security, Fact-Checking, at Pag-iwas sa Fake News'
        ]
    ]
];

if (!isset($modulesMeta[$lsCode])) {
    $lsCode = 'LS1';
}

$mod = $modulesMeta[$lsCode];
$pdfFilename = "DepEd_ALS_{$lsCode}_Learning_Package.pdf";

/**
 * Build a genuine, valid standard PDF-1.4 file
 */
function createDepEdPdf(string $lsCode, array $mod): string {
    $title = $mod['title'];
    $sub = $mod['subtitle'];
    $desc = $mod['desc'];

    // Construct text streams
    $lines = [];
    $lines[] = "DEPARTMENT OF EDUCATION - ALTERNATIVE LEARNING SYSTEM";
    $lines[] = "OPISYAL NA LEARNING ACTIVITY PACKAGE & MODYUL ARALIN";
    $lines[] = "Taong Panuruan 2026 | San Nicolas Community Learning Center";
    $lines[] = "---------------------------------------------------------------------------------------------------------";
    $lines[] = "MODYUL: [{$lsCode}] " . $title;
    $lines[] = "TUTOK: " . $sub;
    $lines[] = "";
    $lines[] = "DESKRIPSYON NG ARALIN:";
    $lines[] = $desc;
    $lines[] = "";
    $lines[] = "I. MGA PANGUNAHING KASANAYAN (LEARNING COMPETENCIES):";
    foreach ($mod['competencies'] as $i => $c) {
        $lines[] = "   " . ($i + 1) . ". " . $c;
    }
    $lines[] = "";
    $lines[] = "II. MGA ARALIN AT GAWAIN SA MODYUL (LESSONS & PORTFOLIO TASKS):";
    foreach ($mod['lessons'] as $i => $l) {
        $lines[] = "   * " . $l;
        $lines[] = "     - Basahin ang gabay sa pahina ng aralin at talakayin sa CLC session.";
        $lines[] = "     - Sagutan ang formative evaluation exercise sa dulo ng aralin.";
        $lines[] = "     - Isumite ang natapos na portfolio worksheet sa Instructional Manager.";
    }
    $lines[] = "";
    $lines[] = "III. GABAY SA EBALWASYON PARA SA DALUBGURO AT MAG-AARAL:";
    $lines[] = "   1. Pagsusuri ng Portfolio batay sa ALS Assessment Rubric.";
    $lines[] = "   2. Pagkumpleto ng Functional Literacy Test (FLT) Competency Sheets.";
    $lines[] = "   3. Paghahanda para sa A&E Accreditation and Equivalency Examination.";
    $lines[] = "";
    $lines[] = "---------------------------------------------------------------------------------------------------------";
    $lines[] = "(C) 2026 DepEd ALS Connect Portal - Pagkatuto para sa Lahat.";

    // Escape parenthesis and backslashes for PDF string literals
    $escaped = array_map(function($line) {
        // transliterate non-ascii chars to safe ASCII for Standard 14 Helvetica font
        $safe = @iconv('UTF-8', 'ASCII//TRANSLIT', $line);
        if ($safe === false) {
            $safe = preg_replace('/[^\x20-\x7E]/', '', $line);
        }
        $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safe);
        return $safe;
    }, $lines);

    // Build PDF content stream
    $streamContent = "BT\n/F1 10 Tf\n40 750 Td\n15 TL\n";
    foreach ($escaped as $idx => $line) {
        if ($idx === 0) {
            $streamContent .= "/F2 13 Tf\n(" . $line . ") Tj\nT*\n/F1 10 Tf\n";
        } elseif ($idx === 4) {
            $streamContent .= "/F2 11 Tf\n(" . $line . ") Tj\nT*\n/F1 10 Tf\n";
        } elseif ($idx === 8 || $idx === 13 || $idx === 21) {
            $streamContent .= "/F2 10.5 Tf\n(" . $line . ") Tj\nT*\n/F1 10 Tf\n";
        } else {
            $streamContent .= "(" . $line . ") Tj\nT*\n";
        }
    }
    $streamContent .= "ET\n";

    $streamLength = strlen($streamContent);

    // Assemble objects
    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>";
    $objects[4] = "<< /Length " . $streamLength . " >>\nstream\n" . $streamContent . "endstream";
    $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

    // Build PDF output with proper xref table
    $out = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $id => $obj) {
        $offsets[$id] = strlen($out);
        $out .= $id . " 0 obj\n" . $obj . "\nendobj\n";
    }

    $xrefOffset = strlen($out);
    $out .= "xref\n";
    $out .= "0 " . (count($objects) + 1) . "\n";
    $out .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $out .= "trailer\n";
    $out .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $out .= "startxref\n";
    $out .= $xrefOffset . "\n";
    $out .= "%%EOF\n";

    return $out;
}

$pdfOutput = createDepEdPdf($lsCode, $mod);

// Direct PDF download response
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $pdfFilename . '"');
header('Content-Length: ' . strlen($pdfOutput));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdfOutput;
exit;
