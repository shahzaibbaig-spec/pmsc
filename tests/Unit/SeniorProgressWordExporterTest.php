<?php

namespace Tests\Unit;

use App\Services\SeniorProgressReportService;
use App\Services\SeniorProgressWordExporter;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class SeniorProgressWordExporterTest extends TestCase
{
    public function test_word_export_preserves_template_assets_and_tables_and_escapes_student_text(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'senior-test-');
        try {
            app(SeniorProgressWordExporter::class)->export([
                'student_name' => 'Ayesha & Ali <Test>', 'student_number' => 'K-123',
                'class_name' => 'Grade 7 A', 'age' => '12', 'teacher_name' => 'Teacher Name',
                'session' => '2026-2027', 'assessment' => 'Midterm',
                'subjects' => [['name' => 'English', 'grade' => 'A', 'progress' => '0', 'effort' => '2']],
                'skills' => ['3'], 'teacher_remarks' => 'Good work.', 'head_remarks' => 'Keep it up.',
            ], $path);
            $zip = new ZipArchive;
            $template = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $this->assertTrue($template->open(resource_path('reports/senior-progress.docx')));
            $xml = $zip->getFromName('word/document.xml');
            $dom = new DOMDocument;
            $this->assertTrue($dom->loadXML($xml));
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $this->assertSame(8, $xpath->query('//w:body/w:tbl')->length);
            $this->assertSame(2, $xpath->query('//w:t[text()="Ayesha & Ali <Test>"]')->length);
            $this->assertStringContainsString('END OF YEAR STUDENTS', $dom->textContent);
            $this->assertStringContainsString('2026-2027', $xml);
            $this->assertStringContainsString('☑ Mirpur', $xml);
            for ($index = 0; $index < $template->numFiles; $index++) {
                $name = $template->getNameIndex($index);
                if ($name !== 'word/document.xml') {
                    $this->assertSame($template->getFromName($name), $zip->getFromName($name), $name);
                }
            }
        } finally {
            if (isset($template)) {
                $template->close();
            }
            if (isset($zip)) {
                $zip->close();
            }
            @unlink($path);
        }
    }

    public function test_senior_class_names_include_only_grades_seven_through_twelve(): void
    {
        foreach (['7', '12', 'Grade 7', 'Class 10', 'Seven', 'Eighth', 'XI', '2nd Year', '9-A'] as $name) {
            $this->assertTrue(SeniorProgressReportService::seniorClass($name), $name);
        }
        foreach (['1', '6', '13', 'Nursery', 'Prep', '70', 'Seventy', 'Pre Ninth'] as $name) {
            $this->assertFalse(SeniorProgressReportService::seniorClass($name), $name);
        }
    }
}
