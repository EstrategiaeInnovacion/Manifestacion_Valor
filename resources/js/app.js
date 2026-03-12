import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Importar todos los iconos necesarios desde Lucide
import {
    createIcons, ArrowRight, ArrowLeft, Ship, Menu, X, ChevronDown, User, UserCircle, LogOut, Settings,
    Home, FileText, Users, Key, Eye, EyeOff, Check, CheckCircle, AlertCircle, AlertTriangle,
    Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock,
    Building2, ChevronRight,
    // Nuevos iconos del Dashboard:
    Layers, UsersRound, Settings2, KeyRound, MoveRight, Briefcase, Play,
    ScanLine, Cpu, BadgeDollarSign, Headset, Send, Ticket, Pencil,
    FileUp, FileClock, ImagePlus,
    // Iconos de ajustes del sistema:
    Bell, Megaphone, BookOpen, Shield, ScrollText, Save, ExternalLink, Inbox
} from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({
        icons: {
            ArrowRight, ArrowLeft, Ship, Menu, X, ChevronDown, User, UserCircle, LogOut, Settings,
            Home, FileText, Users, Key, Eye, EyeOff, Check, CheckCircle, AlertCircle, AlertTriangle,
            Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock,
            Building2, ChevronRight,
            // Nuevos iconos registrados:
            Layers, UsersRound, Settings2, KeyRound, MoveRight, Briefcase, Play,
            ScanLine, Cpu, BadgeDollarSign, Headset, Send, Ticket, Pencil,
            FileUp, FileClock, ImagePlus,
            Bell, Megaphone, BookOpen, Shield, ScrollText, Save, ExternalLink, Inbox
        }
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Panel global de diagnóstico de conectividad VUCEM
// Llamar cuando el servidor devuelve `connectivity_error: true` + `diagnostico`
// ─────────────────────────────────────────────────────────────────────────────
window.mostrarDiagnosticoVucem = function(mensajeError, diagnostico, onClose) {
    document.getElementById('modal-vucem-diagnostico')?.remove();

    const colorCfg = {
        red:    { bg: 'bg-red-50',    border: 'border-red-300',    title: 'text-red-800',    badge: 'bg-red-100 text-red-800',    dot: 'bg-red-400'    },
        yellow: { bg: 'bg-yellow-50', border: 'border-yellow-300', title: 'text-yellow-800', badge: 'bg-yellow-100 text-yellow-800', dot: 'bg-yellow-400' },
        orange: { bg: 'bg-orange-50', border: 'border-orange-300', title: 'text-orange-800', badge: 'bg-orange-100 text-orange-800', dot: 'bg-orange-400' },
        green:  { bg: 'bg-green-50',  border: 'border-green-300',  title: 'text-green-800',  badge: 'bg-green-100 text-green-800',  dot: 'bg-green-400'  },
    };
    const c = colorCfg[diagnostico.color] || colorCfg.red;

    const total = (diagnostico.errores_sistema_30min || 0) + (diagnostico.exitosos_sistema_30min || 0);
    let statsHtml = '';
    if (total > 0) {
        const errU = diagnostico.errores_usuario_24h || 0;
        statsHtml = `
            <div class="flex flex-wrap gap-3 mt-3 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>
                    <span class="text-slate-600">${diagnostico.errores_sistema_30min} error(es) en el sistema (30 min)</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                    <span class="text-slate-600">${diagnostico.exitosos_sistema_30min} envío(s) exitoso(s) (30 min)</span>
                </span>
                ${errU > 0 ? `<span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span>
                    <span class="text-slate-600">${errU} error(es) tuyos en las últimas 24 h</span>
                </span>` : ''}
            </div>`;
    }

    const modal = document.createElement('div');
    modal.id = 'modal-vucem-diagnostico';
    modal.className = 'fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[99999] p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">

            <div class="${c.bg} ${c.border} border-b p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 mt-1">
                        <i data-lucide="${diagnostico.icono}" class="w-9 h-9 ${c.title}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${c.badge} mb-2">
                            DIAGNÓSTICO DE CONECTIVIDAD VUCEM
                        </span>
                        <h3 class="text-base font-bold ${c.title} leading-snug">${diagnostico.titulo}</h3>
                        <p class="text-sm text-slate-600 mt-1 leading-relaxed">${diagnostico.mensaje}</p>
                        ${statsHtml}
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Error recibido</p>
                    <p class="text-sm text-slate-700 break-words">${mensajeError}</p>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex gap-3">
                    <i data-lucide="help-circle" class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5"></i>
                    <p class="text-sm text-blue-800 leading-relaxed">${diagnostico.accion}</p>
                </div>
            </div>

            <div class="px-6 pb-6 flex justify-end">
                <button id="btn-cerrar-diagnostico-vucem"
                    class="px-6 py-2.5 bg-slate-700 hover:bg-slate-800 text-white font-semibold rounded-lg transition-colors text-sm">
                    Entendido
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    if (window.lucide) lucide.createIcons();

    document.getElementById('btn-cerrar-diagnostico-vucem').addEventListener('click', function() {
        modal.remove();
        if (typeof onClose === 'function') onClose();
    });
};