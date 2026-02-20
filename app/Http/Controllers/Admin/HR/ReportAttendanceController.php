<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\{AttendanceService, ReportAttendanceService};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportAttendanceExport;
use Inertia\Inertia;
use Inertia\Response;

class ReportAttendanceController extends Controller
{
    public function index(Request $request, ReportAttendanceService $service): Response
    {
        $startParam = $request->string('start_date')->toString();
        $endParam = $request->string('end_date')->toString();

        $data = $service->generate($startParam, $endParam);

        return Inertia::render('Domains/Admin/HR/Report/Attendance/Index', $data);
    }

    public function markOnTime(Attendance $attendance, AttendanceService $service): RedirectResponse
    {
        $service->overrideLateToPresent($attendance);
        Inertia::flash('toast', [
            'message' => 'Status diubah menjadi Tepat Waktu',
            'type' => 'success',
        ]);
        return redirect()->back();
    }

    public function export(Request $request, ReportAttendanceService $service): BinaryFileResponse
    {
        $startParam = $request->string('start_date')->toString();
        $endParam = $request->string('end_date')->toString();
        return Excel::download(new ReportAttendanceExport($service, $startParam, $endParam), 'attendance_report.xlsx');
    }
}
