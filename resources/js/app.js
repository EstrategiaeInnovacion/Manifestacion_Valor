import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Inicializar Lucide Icons desde npm (reemplaza el CDN de unpkg)
import { createIcons, ArrowRight, Ship, Menu, X, ChevronDown, User, LogOut, Settings, Home, FileText, Users, Key, Eye, EyeOff, Check, AlertTriangle, Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock } from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({
        icons: {
            ArrowRight, Ship, Menu, X, ChevronDown, User, LogOut, Settings,
            Home, FileText, Users, Key, Eye, EyeOff, Check, AlertTriangle,
            Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock
        }
    });
});
