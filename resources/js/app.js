import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Inicializar Lucide Icons desde npm (reemplaza el CDN de unpkg)
import { createIcons, ArrowRight, ArrowLeft, Ship, Menu, X, ChevronDown, User, UserCircle, LogOut, Settings, Home, FileText, Users, Key, Eye, EyeOff, Check, CheckCircle, AlertCircle, AlertTriangle, Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock } from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({
        icons: {
            ArrowRight, ArrowLeft, Ship, Menu, X, ChevronDown, User, UserCircle, LogOut, Settings,
            Home, FileText, Users, Key, Eye, EyeOff, Check, CheckCircle, AlertCircle, AlertTriangle,
            Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock
        }
    });
});
