const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer');
const { marked } = require('marked');

(async () => {
    console.log('Iniciando compilación de PDF del Manual de Usuario...');
    const markdownPath = path.join(__dirname, '../docs/user/manual-usuario.md');
    const pdfOutputPath = path.join(__dirname, '../docs/user/manual-usuario.pdf');

    // Crear la carpeta de salida si no existe
    const pdfDir = path.dirname(pdfOutputPath);
    if (!fs.existsSync(pdfDir)) {
        fs.mkdirSync(pdfDir, { recursive: true });
    }

    if (!fs.existsSync(markdownPath)) {
        console.error('No se encontró el manual de usuario en Markdown:', markdownPath);
        return;
    }

    let markdown = fs.readFileSync(markdownPath, 'utf8');

    // Preprocesar el markdown para dar estilos premium a las alertas e iconos
    // 📋 Qué necesitas
    markdown = markdown.replace(/📋 \*\*Qué necesitas:\*\*(.*)/gi, '<div class="alert alert-requirements"><strong>📋 Requisitos:</strong>$1</div>');
    // ✅ Resultado
    markdown = markdown.replace(/✅ \*\*Resultado:\*\*(.*)/gi, '<div class="alert alert-success"><strong>✅ Resultado:</strong>$1</div>');
    // 🔁 Siguiente paso
    markdown = markdown.replace(/🔁 \*\*Siguiente paso:\*\*(.*)/gi, '<div class="alert alert-next"><strong>🔁 Siguiente paso:</strong>$1</div>');
    // ⚠️ Nota / Advertencia
    markdown = markdown.replace(/> ⚠️ \*\*Nota:\*\*(.*)/gi, '<div class="alert alert-warning"><strong>⚠️ Nota:</strong>$1</div>');
    markdown = markdown.replace(/⚠️ \*\*Nota:\*\*(.*)/gi, '<div class="alert alert-warning"><strong>⚠️ Nota:</strong>$1</div>');
    // 💡 Consejo / Importante
    markdown = markdown.replace(/> 💡 \*\*Consejo:\*\*(.*)/gi, '<div class="alert alert-info"><strong>💡 Consejo:</strong>$1</div>');
    markdown = markdown.replace(/💡 \*\*Consejo:\*\*(.*)/gi, '<div class="alert alert-info"><strong>💡 Consejo:</strong>$1</div>');
    markdown = markdown.replace(/> 💡 \*\*Importante:\*\*(.*)/gi, '<div class="alert alert-info"><strong>💡 Importante:</strong>$1</div>');
    markdown = markdown.replace(/💡 \*\*Importante:\*\*(.*)/gi, '<div class="alert alert-info"><strong>💡 Importante:</strong>$1</div>');

    // Convertir a HTML
    const htmlContent = marked(markdown);

    const baseHref = `file:///${path.join(__dirname, '../docs/user/').replace(/\\/g, '/')}`;

    // Plantilla HTML Completa con Estilos CSS Premium (Modo Claro/Oscuro del manual de usuario)
    const fullHtml = `
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <base href="${baseHref}">
    <title>Manual de Usuario — Sistema de Manifestación de Valor Exterior (MVE)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: #1e293b; /* slate-800 */
            line-height: 1.6;
            margin: 0;
            padding: 40px;
            font-size: 11pt;
        }
        h1, h2, h3, h4, h5 {
            font-family: 'Outfit', sans-serif;
            color: #001a4d; /* Navy dark */
            font-weight: 700;
            margin-top: 1.8em;
            margin-bottom: 0.5em;
            page-break-after: avoid;
        }
        h1 {
            font-size: 28pt;
            border-bottom: 3px solid #003399;
            padding-bottom: 15px;
            margin-top: 0;
            color: #003399;
            text-align: center;
        }
        h2 {
            font-size: 18pt;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            color: #001a4d;
        }
        h3 {
            font-size: 14pt;
            color: #003399;
        }
        h4 {
            font-size: 12pt;
            color: #0055ff;
        }
        p {
            margin-top: 0;
            margin-bottom: 1em;
            text-align: justify;
        }
        ul, ol {
            margin-top: 0;
            margin-bottom: 1.2em;
            padding-left: 20px;
        }
        li {
            margin-bottom: 0.5em;
        }
        hr {
            border: 0;
            height: 1px;
            background: #e2e8f0;
            margin: 30px 0;
            page-break-after: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10pt;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f8fafc;
            color: #001a4d;
            font-weight: 600;
            border-bottom: 2px solid #003399;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        img {
            max-width: 90%;
            height: auto;
            display: block;
            margin: 25px auto;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }
        blockquote {
            border-left: 4px solid #003399;
            background-color: #f8fafc;
            padding: 10px 15px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
            font-style: italic;
        }
        code {
            font-family: monospace;
            background-color: #f1f5f9;
            padding: 2px 4px;
            border-radius: 4px;
            font-size: 9.5pt;
        }
        /* Alertas */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 10pt;
            border: 1px solid transparent;
            page-break-inside: avoid;
        }
        .alert-requirements {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1e3a8a;
        }
        .alert-success {
            background-color: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }
        .alert-next {
            background-color: #f5f3ff;
            border-color: #ddd6fe;
            color: #5b21b6;
        }
        .alert-warning {
            background-color: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .alert-info {
            background-color: #f0fdfa;
            border-color: #99f6e4;
            color: #115e59;
        }
        /* Portada */
        .cover {
            height: 90vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            page-break-after: always;
        }
        .cover-title {
            font-family: 'Outfit', sans-serif;
            font-size: 32pt;
            color: #001a4d;
            margin-bottom: 10px;
            font-weight: 800;
        }
        .cover-subtitle {
            font-size: 16pt;
            color: #003399;
            margin-bottom: 50px;
        }
        .cover-meta {
            margin-top: 100px;
            font-size: 11pt;
            color: #64748b;
            line-height: 2;
        }
        .logo-img {
            max-width: 180px;
            border: none;
            box-shadow: none;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>

    <!-- Portada -->
    <div class="cover">
        <img class="logo-img" src="file:///${path.join(__dirname, '../public/Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png').replace(/\\/g, '/')}" alt="Logo MVE">
        <div class="cover-title">Manual de Usuario</div>
        <div class="cover-subtitle">Sistema de Manifestación de Valor Exterior (MVE)</div>
        
        <div class="cover-meta">
            <strong>Versión:</strong> 2.0<br>
            <strong>Fecha de actualización:</strong> Junio 2026<br>
            <strong>Estrategia e Innovación S.A. de C.V.</strong>
        </div>
    </div>

    <!-- Contenido convertido de Markdown -->
    ${htmlContent}

</body>
</html>
    `;

    // Guardar el HTML generado temporalmente para depuración
    const tempHtmlPath = path.join(__dirname, 'temp_manual.html');
    fs.writeFileSync(tempHtmlPath, fullHtml);

    // Lanzar Puppeteer para imprimir el PDF
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();

    console.log('Cargando contenido HTML en Puppeteer...');
    await page.goto(`file:///${tempHtmlPath.replace(/\\/g, '/')}`, { waitUntil: 'networkidle2' });

    console.log('Imprimiendo PDF...');
    await page.pdf({
        path: pdfOutputPath,
        format: 'A4',
        margin: {
            top: '20mm',
            bottom: '20mm',
            left: '20mm',
            right: '20mm'
        },
        printBackground: true,
        displayHeaderFooter: true,
        headerTemplate: '<div style="font-size: 8px; font-family: sans-serif; width: 100%; text-align: right; padding-right: 20px; color: #94a3b8;">Manual de Usuario MVE</div>',
        footerTemplate: '<div style="font-size: 8px; font-family: sans-serif; width: 100%; text-align: center; color: #94a3b8;"><span class="pageNumber"></span> / <span class="totalPages"></span></div>'
    });

    console.log('✅ PDF generado exitosamente en:', pdfOutputPath);

    await browser.close();
    fs.unlinkSync(tempHtmlPath);
    console.log('Compilación finalizada.');
})();
