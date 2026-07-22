<?php

namespace App\Http\Controllers;

use App\Models\GlosaImport;
use App\Models\Glosa501DatosGenerales;
use App\Models\Glosa505Factura;
use App\Models\Glosa510Contribucion;
use App\Models\Glosa551Partida;
use App\Models\Glosa557ContribucionPartida;
use App\Models\Glosa701Rectificacion;
use App\Services\GlosaDataStageService;
use App\Services\GlosaExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class GlosaController extends Controller
{
    /**
     * Muestra el Dashboard analítico interactivo y la zona de carga de Data Stage
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $adminId = ($user->role === 'Admin' || $user->role === 'SuperAdmin') 
            ? $user->id 
            : ($user->created_by ?? $user->id);

        $imports = GlosaImport::where('admin_id', $adminId)
            ->latest()
            ->paginate(10);

        // Lista de RFCs disponibles para el filtro
        $rfcs = Glosa501DatosGenerales::where('admin_id', $adminId)
            ->whereNotNull('rfc')
            ->distinct()
            ->pluck('rfc');

        // Lista de Aduanas disponibles
        $aduanas = Glosa501DatosGenerales::where('admin_id', $adminId)
            ->whereNotNull('seccion_aduanera')
            ->distinct()
            ->pluck('seccion_aduanera');

        return view('glosa.index', compact('imports', 'rfcs', 'aduanas'));
    }

    /**
     * Procesa la carga manual (drag & drop) o automática de un archivo ZIP de Data Stage
     */
    public function upload(Request $request, GlosaDataStageService $ingestService)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:102400', // Máx 100MB
        ]);

        try {
            $user = $request->user();
            $import = $ingestService->processZipFile($request->file('zip_file'), $user);

            return redirect()->route('glosa.index')->with('success', 
                "Archivo Data Stage '{$import->original_filename}' procesado exitosamente. Total de Pedimentos: {$import->total_pedimentos}, Partidas: {$import->total_partidas}."
            );
        } catch (Exception $e) {
            Log::error('[GLOSA CONTROLLER] Error al cargar ZIP: ' . $e->getMessage());
            return back()->withErrors(['zip_file' => 'Error al procesar el archivo ZIP: ' . $e->getMessage()]);
        }
    }

    /**
     * Descarga el reporte Excel (.xlsx) estructurado en 26 hojas por bóveda
     */
    public function exportExcel(GlosaImport $import, GlosaExcelExportService $exportService)
    {
        $user = auth()->user();
        $adminId = ($user->role === 'Admin' || $user->role === 'SuperAdmin') 
            ? $user->id 
            : ($user->created_by ?? $user->id);

        if ($import->admin_id !== $adminId && $user->role !== 'SuperAdmin') {
            abort(403, 'No tiene autorización para descargar esta exportación.');
        }

        try {
            $filePath = $exportService->generateExcel($import);
            $downloadName = "Glosa_DataStage_{$import->original_filename}.xlsx";

            return response()->download($filePath, $downloadName)->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Log::error('[GLOSA CONTROLLER] Error al exportar Excel: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
        }
    }

    /**
     * API JSON Data Endpoint para la alimentación interactiva del Dashboard
     */
    public function getDashboardMetrics(Request $request)
    {
        $user = $request->user();
        $adminId = ($user->role === 'Admin' || $user->role === 'SuperAdmin') 
            ? $user->id 
            : ($user->created_by ?? $user->id);

        // Filtros globales
        $startDate     = $request->query('start_date');
        $endDate       = $request->query('end_date');
        $rfc           = $request->query('rfc');
        $tipoOperacion = $request->query('tipo_operacion'); // 1: Imp, 2: Exp
        $aduana        = $request->query('aduana');

        $query501 = Glosa501DatosGenerales::where('admin_id', $adminId);

        if ($startDate) {
            $query501->whereDate('fecha_pago_real', '>=', $startDate);
        }
        if ($endDate) {
            $query501->whereDate('fecha_pago_real', '<=', $endDate);
        }
        if ($rfc) {
            $query501->where('rfc', $rfc);
        }
        if ($tipoOperacion) {
            $query501->where('tipo_operacion', $tipoOperacion);
        }
        if ($aduana) {
            $query501->where('seccion_aduanera', $aduana);
        }

        $filteredClaves = (clone $query501)->pluck('clave_operacion');

        // 1. KPIs Generales
        $totalOperaciones = count($filteredClaves);
        $totalImportaciones = (clone $query501)->where('tipo_operacion', '1')->count();
        $totalExportaciones = (clone $query501)->where('tipo_operacion', '2')->count();

        // Valor Comercial en USD (Bóveda 505)
        $valorComercialUSD = Glosa505Factura::whereIn('clave_operacion', $filteredClaves)->sum('valor_dolares');

        // Total Impuestos Pagados (Bóveda 510 y 557)
        $totalContribPedimento = Glosa510Contribucion::whereIn('clave_operacion', $filteredClaves)->sum('importe');
        $totalContribPartida   = Glosa557ContribucionPartida::whereIn('clave_operacion', $filteredClaves)->sum('importe');
        $totalImpuestos = $totalContribPedimento + $totalContribPartida;

        // Desglose por clave de contribución (IVA, IGI, DTA)
        $contribsDesglose = Glosa510Contribucion::whereIn('clave_operacion', $filteredClaves)
            ->select('clave_contribucion', DB::raw('SUM(importe) as total'))
            ->groupBy('clave_contribucion')
            ->pluck('total', 'clave_contribucion');

        // 2. Gráfico de Tendencias Mensuales (Compatible con SQLite y MySQL)
        $driver = DB::connection()->getDriverName();
        $dateFormatRaw = ($driver === 'sqlite') 
            ? "strftime('%Y-%m', fecha_pago_real)" 
            : "DATE_FORMAT(fecha_pago_real, '%Y-%m')";

        $tendenciaMensual = (clone $query501)
            ->select(DB::raw("{$dateFormatRaw} as mes"), DB::raw('COUNT(*) as total'))
            ->whereNotNull('fecha_pago_real')
            ->groupBy('mes')
            ->orderBy('mes', 'ASC')
            ->get();

        // 3. Top 10 Fracciones Arancelarias (Bóveda 551)
        $topFracciones = Glosa551Partida::whereIn('clave_operacion', $filteredClaves)
            ->select('fraccion_arancelaria', DB::raw('COUNT(*) as total_operaciones'), DB::raw('SUM(valor_dolares) as total_valor_usd'))
            ->whereNotNull('fraccion_arancelaria')
            ->groupBy('fraccion_arancelaria')
            ->orderByDesc('total_operaciones')
            ->limit(10)
            ->get();

        // 4. Panel de Riesgo y Compliance (Bóveda 701 - Rectificaciones)
        $totalRectificaciones = Glosa701Rectificacion::whereIn('clave_operacion', $filteredClaves)->count();
        $tasaRectificacion = $totalOperaciones > 0 ? round(($totalRectificaciones / $totalOperaciones) * 100, 2) : 0;

        // 5. Operaciones por Aduana
        $porAduana = (clone $query501)
            ->select('seccion_aduanera', DB::raw('COUNT(*) as total'))
            ->whereNotNull('seccion_aduanera')
            ->groupBy('seccion_aduanera')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return response()->json([
            'kpis' => [
                'total_operaciones'   => $totalOperaciones,
                'importaciones'       => $totalImportaciones,
                'exportaciones'       => $totalExportaciones,
                'valor_comercial_usd' => round($valorComercialUSD, 2),
                'total_impuestos'     => round($totalImpuestos, 2),
                'contribuciones'      => $contribsDesglose,
            ],
            'compliance' => [
                'total_rectificaciones' => $totalRectificaciones,
                'tasa_rectificacion'    => $tasaRectificacion,
            ],
            'charts' => [
                'tendencia_mensual' => $tendenciaMensual,
                'top_fracciones'    => $topFracciones,
                'por_aduana'        => $porAduana,
            ]
        ]);
    }
}
