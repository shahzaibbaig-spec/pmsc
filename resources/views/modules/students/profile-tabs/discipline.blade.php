@include('students.partials.discipline-history', [
    'student' => $student,
    'disciplineReports' => $disciplineReports ?? collect(),
    'disciplineComplaints' => $disciplineComplaints ?? collect(),
    'sportsObservations' => $sportsObservations ?? collect(),
    'openReportCount' => $openReportCount ?? 0,
    'openComplaintCount' => $openComplaintCount ?? 0,
    'openSportsCount' => $openSportsCount ?? 0,
    'openCount' => $openCount ?? 0,
])

