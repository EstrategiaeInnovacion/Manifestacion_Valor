import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Importar todos los iconos necesarios desde Lucide
import {
    createIcons, ArrowRight, ArrowLeft, Ship, Menu, X, ChevronDown, User, UserCircle, LogOut, Settings,
    Home, FileText, Users, Key, Eye, EyeOff, Check, CheckCircle, AlertCircle, AlertTriangle,
    Info, Upload, Download, Trash2, Edit, Plus, Search, Filter, Lock, Unlock,
    Building2, ChevronRight,
    // Nuevos iconos del Dashboard:
    Layers, UsersRound, Settings2, KeyRound, MoveRight, Briefcase, Play,
    ScanLine, Cpu, BadgeDollarSign, Headset, Send, Ticket, Pencil,
    FileUp, FileClock, ImagePlus
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
            FileUp, FileClock, ImagePlus
        }
    });
});