<?php

namespace App\Http\Controllers;

use App\Models\MvAcuse;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\VucemErrorLog;
use App\Services\VucemDiagnosticService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    public function index()
    {
        // Semana actual: lunes 00:00:00 hasta ahora
        $inicioSemana = now()->startOfWeek(Carbon::MONDAY);
        $hace7dias    = now()->subDays(7);   // solo para tickets
        $hace30dias   = now()->subDays(30);

        // ── VUCEM (semana actual) ──────────────────────────────────────────

        $vucemErroresTotal  = VucemErrorLog::where('created_at', '>=', $inicioSemana)->count();
        $vucemExitososTotal = MvAcuse::where('fecha_envio', '>=', $inicioSemana)->count();
        $vucemTotalOps      = $vucemErroresTotal + $vucemExitososTotal;
        $vucemTasaError     = $vucemTotalOps > 0
            ? round(($vucemErroresTotal / $vucemTotalOps) * 100, 1)
            : 0;

        // Errores hoy
        $vucemErroresHoy = VucemErrorLog::whereDate('created_at', today())->count();

        // Por servicio (semana actual)
        $vucemPorServicio = VucemErrorLog::where('created_at', '>=', $inicioSemana)
            ->select('servicio', DB::raw('count(*) as total'))
            ->groupBy('servicio')
            ->orderByDesc('total')
            ->get();

        // Por tipo de error (semana actual)
        $vucemPorTipo = VucemErrorLog::where('created_at', '>=', $inicioSemana)
            ->select('tipo_error', DB::raw('count(*) as total'))
            ->groupBy('tipo_error')
            ->orderByDesc('total')
            ->get();

        // Errores por día — días transcurridos de la semana actual (lun → hoy)
        $erroresPorDia = VucemErrorLog::where('created_at', '>=', $inicioSemana)
            ->select(DB::raw('DATE(created_at) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->get()
            ->keyBy('dia');

        $exitosPorDia = MvAcuse::where('fecha_envio', '>=', $inicioSemana)
            ->select(DB::raw('DATE(fecha_envio) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->get()
            ->keyBy('dia');

        // Construir gráfica: un bar por cada día desde el lunes hasta hoy
        $diasSemana = (int) now()->startOfWeek(Carbon::MONDAY)->startOfDay()
                                  ->diffInDays(now()->startOfDay()) + 1;
        $diasChart = [];
        for ($i = $diasSemana - 1; $i >= 0; $i--) {
            $dia = now()->subDays($i)->toDateString();
            $diasChart[] = [
                'label'    => now()->subDays($i)->locale('es')->isoFormat('ddd D'),
                'errores'  => (int) ($erroresPorDia[$dia]['total'] ?? 0),
                'exitosos' => (int) ($exitosPorDia[$dia]['total'] ?? 0),
            ];
        }

        // Top usuarios con más errores (semana actual)
        $topErrUserIds = VucemErrorLog::where('created_at', '>=', $inicioSemana)
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'user_id');

        $topUsers = User::whereIn('id', $topErrUserIds->keys())
            ->select('id', 'full_name', 'email')
            ->get()
            ->keyBy('id');

        $vucemTopUsuarios = $topErrUserIds->map(fn($total, $userId) => [
            'user'  => $topUsers[$userId] ?? null,
            'total' => $total,
        ])->values();

        // Estado actual del sistema (cacheado) y si hay override activo
        $estadoActual  = VucemDiagnosticService::getEstadoSistema();
        $tieneOverride = VucemDiagnosticService::tieneOverride();

        // ── TICKETS ────────────────────────────────────────────────────────

        $ticketsPorStatus = SupportTicket::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $ticketsPorCategoria = SupportTicket::where('created_at', '>=', $hace30dias)
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // Tendencia últimos 7 días
        $ticketsDias = SupportTicket::where('created_at', '>=', $hace7dias)
            ->select(DB::raw('DATE(created_at) as dia'), DB::raw('count(*) as total'))
            ->groupBy('dia')
            ->get()
            ->keyBy('dia');

        $ticketsDiasChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = now()->subDays($i)->toDateString();
            $ticketsDiasChart[] = [
                'label' => now()->subDays($i)->locale('es')->isoFormat('ddd D'),
                'total' => (int) ($ticketsDias[$dia]['total'] ?? 0),
            ];
        }

        $ticketsTotal    = SupportTicket::count();
        $ticketsTotales7d = SupportTicket::where('created_at', '>=', $hace7dias)->count();

        $ticketsRecientes = SupportTicket::with('user:id,full_name,email')
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.estadisticas', compact(
            'vucemErroresTotal', 'vucemExitososTotal', 'vucemTasaError', 'vucemTotalOps',
            'vucemErroresHoy', 'vucemPorServicio', 'vucemPorTipo', 'diasChart',
            'vucemTopUsuarios', 'estadoActual', 'tieneOverride',
            'ticketsPorStatus', 'ticketsPorCategoria', 'ticketsDiasChart',
            'ticketsTotal', 'ticketsTotales7d', 'ticketsRecientes'
        ));
    }

    /**
     * Fuerza manualmente el estado VUCEM a OPERANDO durante 2 horas.
     */
    public function forzarOperando()
    {
        VucemDiagnosticService::forzarOperando();
        return back()->with('success_vucem', 'Estado VUCEM forzado a OPERANDO por 2 horas. La alerta desaparecerá en todos los paneles.');
    }

    /**
     * Elimina el override manual y restaura el diagnóstico automático.
     */
    public function limpiarOverride()
    {
        VucemDiagnosticService::limpiarOverride();
        return back()->with('success_vucem', 'Override eliminado. El sistema determinará el estado automáticamente según los últimos 30 minutos.');
    }
}
