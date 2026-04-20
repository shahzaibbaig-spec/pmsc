<?php

namespace App\Modules\Teachers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PrincipalTeacherListController extends Controller
{
    public function index(): View
    {
        return view('modules.principal.teachers.list');
    }

    public function exportActiveDocx(): BinaryFileResponse
    {
        $rows = Teacher::query()
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->select([
                'teachers.teacher_id',
                'teachers.employee_code',
                'teachers.designation',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->where(function ($query): void {
                $query->where('users.status', 'active')
                    ->orWhereNull('users.status');
            })
            ->orderBy('users.name')
            ->orderBy('teachers.id')
            ->get()
            ->map(function (Teacher $teacher): array {
                $name = trim((string) ($teacher->getAttribute('user_name') ?? ''));
                $email = trim((string) ($teacher->getAttribute('user_email') ?? ''));
                $employeeCode = trim((string) ($teacher->employee_code ?? ''));
                $designation = trim((string) ($teacher->designation ?? ''));
                $teacherId = trim((string) ($teacher->teacher_id ?? ''));

                return [
                    'teacher_id' => $teacherId !== '' ? $teacherId : '-',
                    'name' => $name !== '' ? $name : '-',
                    'email' => $email !== '' ? $email : '-',
                    'employee_code' => $employeeCode !== '' ? $employeeCode : '-',
                    'designation' => $designation !== '' ? $designation : '-',
                ];
            })
            ->values()
            ->all();

        $docxPath = $this->buildActiveTeachersDocx($rows);
        $filename = 'active_teachers_'.now()->format('Y_m_d_His').'.docx';

        return response()->download(
            $docxPath,
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]
        )->deleteFileAfterSend(true);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'sort' => ['nullable', 'string', 'in:name,email,employee_code,designation,assignments_count,status'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $sort = (string) ($validated['sort'] ?? 'name');
        $dir = (string) ($validated['dir'] ?? 'asc');

        $sortColumn = match ($sort) {
            'name' => 'users.name',
            'email' => 'users.email',
            'employee_code' => 'teachers.employee_code',
            'designation' => 'teachers.designation',
            'assignments_count' => 'assignments_count',
            'status' => 'users.status',
            default => 'users.name',
        };

        $contains = '%'.$search.'%';
        $prefix = $search.'%';

        $query = Teacher::query()
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->select([
                'teachers.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.status as user_status',
            ])
            ->withCount('assignments')
            ->when($search !== '', function ($builder) use ($contains, $prefix): void {
                $builder->where(function ($q) use ($contains, $prefix): void {
                    $q->where('users.name', 'like', $contains)
                        ->orWhere('users.email', 'like', $contains)
                        ->orWhere('teachers.teacher_id', 'like', $prefix)
                        ->orWhere('teachers.employee_code', 'like', $prefix)
                        ->orWhere('teachers.designation', 'like', $contains);
                });
            })
            ->orderBy($sortColumn, $dir)
            ->orderBy('teachers.id');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function (Teacher $teacher): array {
                return [
                    'id' => $teacher->id,
                    'teacher_id' => $teacher->teacher_id,
                    'name' => (string) ($teacher->getAttribute('user_name') ?? ''),
                    'email' => (string) ($teacher->getAttribute('user_email') ?? ''),
                    'employee_code' => $teacher->employee_code,
                    'designation' => $teacher->designation,
                    'status' => (string) ($teacher->getAttribute('user_status') ?? 'active'),
                    'assignments_count' => (int) ($teacher->assignments_count ?? 0),
                ];
            })->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'sort' => [
                'by' => $sort,
                'dir' => $dir,
            ],
        ]);
    }

    /**
     * @param array<int, array{
     *   teacher_id:string,
     *   name:string,
     *   email:string,
     *   employee_code:string,
     *   designation:string
     * }> $rows
     */
    private function buildActiveTeachersDocx(array $rows): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is required for DOCX export.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'active_teachers_');
        if (! is_string($tempPath) || $tempPath === '') {
            throw new RuntimeException('Unable to create temporary DOCX export file.');
        }

        $archive = new ZipArchive();
        if ($archive->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempPath);
            throw new RuntimeException('Unable to initialize DOCX export file.');
        }

        $contentTypesXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML;

        $relsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;

        $archive->addFromString('[Content_Types].xml', $contentTypesXml);
        $archive->addFromString('_rels/.rels', $relsXml);
        $archive->addEmptyDir('word');
        $archive->addFromString('word/document.xml', $this->buildActiveTeachersDocumentXml($rows));
        $archive->close();

        return $tempPath;
    }

    /**
     * @param array<int, array{
     *   teacher_id:string,
     *   name:string,
     *   email:string,
     *   employee_code:string,
     *   designation:string
     * }> $rows
     */
    private function buildActiveTeachersDocumentXml(array $rows): string
    {
        $tableRowsXml = '<w:tr>'
            .$this->docxTableCell('Teacher ID', true)
            .$this->docxTableCell('Name', true)
            .$this->docxTableCell('Email', true)
            .$this->docxTableCell('Employee Code', true)
            .$this->docxTableCell('Designation', true)
            .'</w:tr>';

        foreach ($rows as $row) {
            $tableRowsXml .= '<w:tr>'
                .$this->docxTableCell((string) ($row['teacher_id'] ?? '-'))
                .$this->docxTableCell((string) ($row['name'] ?? '-'))
                .$this->docxTableCell((string) ($row['email'] ?? '-'))
                .$this->docxTableCell((string) ($row['employee_code'] ?? '-'))
                .$this->docxTableCell((string) ($row['designation'] ?? '-'))
                .'</w:tr>';
        }

        $generatedOn = now()->format('Y-m-d H:i');
        $total = count($rows);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'
            .'<w:p><w:r><w:rPr><w:b/><w:sz w:val="30"/></w:rPr><w:t>Active Teachers List</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t xml:space="preserve">Generated on: '.$this->docxEscape($generatedOn).'</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t xml:space="preserve">Total active teachers: '.$this->docxEscape((string) $total).'</w:t></w:r></w:p>'
            .'<w:tbl>'
            .'<w:tblPr>'
            .'<w:tblW w:w="0" w:type="auto"/>'
            .'<w:tblBorders>'
            .'<w:top w:val="single" w:sz="8" w:space="0" w:color="000000"/>'
            .'<w:left w:val="single" w:sz="8" w:space="0" w:color="000000"/>'
            .'<w:bottom w:val="single" w:sz="8" w:space="0" w:color="000000"/>'
            .'<w:right w:val="single" w:sz="8" w:space="0" w:color="000000"/>'
            .'<w:insideH w:val="single" w:sz="6" w:space="0" w:color="A0A0A0"/>'
            .'<w:insideV w:val="single" w:sz="6" w:space="0" w:color="A0A0A0"/>'
            .'</w:tblBorders>'
            .'</w:tblPr>'
            .$tableRowsXml
            .'</w:tbl>'
            .'<w:sectPr>'
            .'<w:pgSz w:w="12240" w:h="15840"/>'
            .'<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>'
            .'</w:sectPr>'
            .'</w:body>'
            .'</w:document>';
    }

    private function docxTableCell(string $value, bool $header = false): string
    {
        $runProperties = $header ? '<w:rPr><w:b/></w:rPr>' : '';

        return '<w:tc>'
            .'<w:tcPr><w:tcW w:w="0" w:type="auto"/></w:tcPr>'
            .'<w:p><w:r>'.$runProperties.'<w:t xml:space="preserve">'.$this->docxEscape($value).'</w:t></w:r></w:p>'
            .'</w:tc>';
    }

    private function docxEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
