const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
    console.log('Iniciando captura de pantallas del MVE...');
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });

    const outputDir = path.join(__dirname, '../docs/user/images');
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    try {
        // 1. Pantalla de Login
        console.log('Capturando pantalla de Login...');
        await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '01-login.png') });
        console.log('✅ 01-login.png guardado.');

        // Iniciar Sesión
        console.log('Iniciando sesión como SuperAdmin...');
        await page.type('input#login', 'guillermo.aguilera@estrategiaeinnovacion.com.mx');
        await page.type('input#password', 'Estrategia1');
        
        // Hacer clic en Iniciar Sesión
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle2' })
        ]);
        console.log('Sesión iniciada con éxito.');

        // 2. Dashboard
        console.log('Capturando Dashboard...');
        await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '02-dashboard.png') });
        console.log('✅ 02-dashboard.png guardado.');

        // 3. Solicitantes
        console.log('Capturando Módulo de Solicitantes...');
        await page.goto('http://127.0.0.1:8000/applicants', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '03-solicitantes.png') });
        console.log('✅ 03-solicitantes.png guardado.');

        // 4. Seleccionar Solicitante para MVE
        console.log('Capturando Selección de Solicitante para MVE...');
        await page.goto('http://127.0.0.1:8000/mve/select-applicant', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '04-mve-select-applicant.png') });
        console.log('✅ 04-mve-select-applicant.png guardado.');

        // 5. Crear MVE Manualmente (Paso 1)
        console.log('Capturando Asistente de Creación MVE (Paso 1)...');
        await page.goto('http://127.0.0.1:8000/mve/manual/1', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '05-mve-create-manual.png') });
        console.log('✅ 05-mve-create-manual.png guardado.');

        // 6. Crear MVE mediante Archivo M
        console.log('Capturando Carga de Archivo M...');
        await page.goto('http://127.0.0.1:8000/mve/archivo-m/1', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '06-mve-upload-file.png') });
        console.log('✅ 06-mve-upload-file.png guardado.');

        // 7. MVE Pendientes
        console.log('Capturando Bandeja de Pendientes...');
        await page.goto('http://127.0.0.1:8000/mve/pendientes', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '07-mve-pendientes.png') });
        console.log('✅ 07-mve-pendientes.png guardado.');

        // 8. Consulta de COVE
        console.log('Capturando Consulta de COVE...');
        await page.goto('http://127.0.0.1:8000/cove/consulta', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '08-cove-consulta.png') });
        console.log('✅ 08-cove-consulta.png guardado.');

        // 9. Digitalización
        console.log('Capturando Módulo de Digitalización...');
        await page.goto('http://127.0.0.1:8000/digitalizacion', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '09-digitalizacion.png') });
        console.log('✅ 09-digitalizacion.png guardado.');

        // 10. FAQs
        console.log('Capturando Módulo FAQs...');
        await page.goto('http://127.0.0.1:8000/faqs', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '10-faqs.png') });
        console.log('✅ 10-faqs.png guardado.');

        // 11. Tickets
        console.log('Capturando Módulo Tickets...');
        await page.goto('http://127.0.0.1:8000/tickets', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '11-tickets.png') });
        console.log('✅ 11-tickets.png guardado.');

        // 12. Gestión de Usuarios
        console.log('Capturando Gestión de Usuarios...');
        await page.goto('http://127.0.0.1:8000/users', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '12-users.png') });
        console.log('✅ 12-users.png guardado.');

        // 13. Ajustes de Administración
        console.log('Capturando Ajustes Generales...');
        await page.goto('http://127.0.0.1:8000/admin/settings', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '13-admin-settings.png') });
        console.log('✅ 13-admin-settings.png guardado.');

    } catch (error) {
        console.error('Error durante la captura:', error);
    } finally {
        await browser.close();
        console.log('Captura de pantalla finalizada.');
    }
})();
