import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Importar Building2 y ChevronRight junto con los demás
import {
    createIcons, ArrowRight, ArrowLeft, Ship, Menu, X, ChevronDown, User, UserCircle, LogOut, Settings,
    Home, FileText, Users, Key, Eye, EyeOff, Check, CheckCircle, AlertCircle, AlertTriangle,
    Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock,
    Building2, ChevronRight // <-- Nuevos iconos agregados
} from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({
        icons: {
            ArrowRight, ArrowLeft, Ship, Menu, X, ChevronDown, User, UserCircle, LogOut, Settings,
            Home, FileText, Users, Key, Eye, EyeOff, Check, CheckCircle, AlertCircle, AlertTriangle,
            Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock,
            Building2, ChevronRight // <-- Registrados aquí
        }
    });
});