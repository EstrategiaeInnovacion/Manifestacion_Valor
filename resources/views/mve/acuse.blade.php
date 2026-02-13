<x-app-layout>
    <x-slot name="title">Acuse de Recibo</x-slot>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Acuse de Manifestación de Valor</h1>
            <p class="text-gray-600">Información del envío y respuesta de VUCEM</p>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">¡Éxito!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Estado del Acuse -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-semibold text-gray-800">Estado del Envío</h2>
                <span class="px-4 py-2 rounded-full text-sm font-semibold
                    @if($acuse->status === 'ACEPTADO') bg-green-100 text-green-800
                    @elseif($acuse->status === 'RECHAZADO') bg-red-100 text-red-800
                    @else bg-yellow-100 text-yellow-800
                    @endif">
                    {{ $acuse->status }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información del Envío -->
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">Folio de Manifestación</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $acuse->folio_manifestacion }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Número de Pedimento</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $acuse->numero_pedimento ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Número de COVE</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $acuse->numero_cove ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Fechas -->
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">Fecha de Envío</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $acuse->fecha_envio ? $acuse->fecha_envio->format('d/m/Y H:i:s') : 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Fecha de Respuesta</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $acuse->fecha_respuesta ? $acuse->fecha_respuesta->format('d/m/Y H:i:s') : 'Pendiente' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Mensaje de VUCEM -->
            @if($acuse->mensaje_vucem)
                <div class="mt-6 pt-6 border-t">
                    <p class="text-sm text-gray-600 mb-2">Mensaje de VUCEM:</p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-800">{{ $acuse->mensaje_vucem }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Información del Solicitante -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Información del Solicitante</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Razón Social</p>
                    <p class="font-medium text-gray-900">{{ $manifestacion->applicant->business_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">RFC</p>
                    <p class="font-medium text-gray-900">{{ $manifestacion->applicant->rfc }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Correo Electrónico</p>
                    <p class="font-medium text-gray-900">{{ $manifestacion->applicant->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Teléfono</p>
                    <p class="font-medium text-gray-900">{{ $manifestacion->applicant->phone ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Descargas Disponibles -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Documentos Disponibles</h2>
            <div class="space-y-3">
                <!-- PDF del Acuse -->
                @if($acuse->acuse_pdf)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-10 w-10 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Acuse de Manifestación (PDF)</p>
                                <p class="text-sm text-gray-600">Documento oficial de VUCEM</p>
                            </div>
                        </div>
                        <a href="{{ route('mve.acuse.pdf', $manifestacion->id) }}" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Descargar PDF
                        </a>
                    </div>
                @else
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                        <svg class="h-10 w-10 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-700">Acuse PDF no disponible</p>
                            <p class="text-sm text-gray-500">El PDF aún no ha sido generado por VUCEM</p>
                        </div>
                    </div>
                @endif

                <!-- XML de Respuesta -->
                @if($acuse->xml_respuesta)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-10 w-10 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Respuesta de VUCEM (XML)</p>
                                <p class="text-sm text-gray-600">Respuesta completa del servicio</p>
                            </div>
                        </div>
                        <a href="{{ route('mve.acuse.xml', $manifestacion->id) }}" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Descargar XML
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}" 
                class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Volver al Dashboard
            </a>

            <div class="flex space-x-3">
                @if($acuse->status === 'RECHAZADO')
                    <a href="{{ route('mve.firmar', $manifestacion->id) }}" 
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reintentar Envío
                    </a>
                @endif
                
                <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, nav, footer, button {
        display: none !important;
    }
}
</style>
</x-app-layout>
