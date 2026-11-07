/* Shared visual identity for the PDF and browser print report. */
window.ProgrammingReports = (() => {
    'use strict';
    const green = '#176542', ink = '#26352d', muted = '#6b756e', border = '#e6ebe7';
    const html = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character]));

    function customizePdf(doc, meta) {
        const source = doc.content.find(item => item.table)?.table;
        if (!source) return;
        const headings = source.body[0].map(cell => typeof cell === 'object' ? cell.text : cell);
        const weights = { Fecha: 65, Estado: 72, Zona: 75, Turno: 55, 'Vehículo': 95, Conductor: 120, Ayudantes: 130 };
        const total = headings.reduce((sum, heading) => sum + (weights[heading] || 90), 0);
        const width = 841.89 - 72;
        source.widths = headings.map(heading => (width * (weights[heading] || 90) / total) - 16);
        source.headerRows = 1;
        source.dontBreakRows = true;
        source.body = source.body.map((row, index) => row.map(cell => ({
            text: String(typeof cell === 'object' ? cell.text ?? '' : cell),
            color: index === 0 ? '#ffffff' : ink,
            bold: index === 0,
            fontSize: index === 0 ? 8 : 8.5,
            margin: [0, 2, 0, 2]
        })));
        doc.pageSize = 'A4';
        doc.pageOrientation = 'landscape';
        doc.pageMargins = [36, 34, 36, 42];
        doc.defaultStyle = { font: 'Roboto', fontSize: 9, color: ink, lineHeight: 1.2 };
        doc.info = { title: 'Programaciones | SGRS JLO', author: 'SGRS JLO', subject: 'Reporte de recorridos de recolección' };
        doc.content = [
            { columns: [
                { stack: [{ text: 'SGRS JLO', fontSize: 15, bold: true, color: green }, { text: 'Sistema de Gestión de Residuos Sólidos', fontSize: 8, color: muted, margin: [0, 4, 0, 0] }] },
                { stack: [{ text: 'REPORTE DE PROGRAMACIONES', fontSize: 8, bold: true, color: green, alignment: 'right' }, { text: `Emitido: ${meta.issued}`, fontSize: 8, color: muted, alignment: 'right', margin: [0, 4, 0, 0] }] }
            ], margin: [0, 0, 0, 15] },
            { canvas: [{ type: 'line', x1: 0, y1: 0, x2: width, y2: 0, lineWidth: 2, lineColor: green }], margin: [0, 0, 0, 17] },
            { text: 'Programaciones', fontSize: 23, bold: true, color: ink, margin: [0, 0, 0, 4] },
            { text: 'Planificación de vehículos y personal', fontSize: 10, color: muted, margin: [0, 0, 0, 15] },
            { columns: [
                { text: `${meta.count} registros en el reporte`, bold: true, color: green },
                { text: `${meta.scheduled} programados  /  ${meta.finished} finalizados`, color: muted, alignment: 'right' }
            ], margin: [0, 0, 0, 10] },
            { text: meta.filters.join('  |  '), color: muted, fontSize: 8, margin: [0, 0, 0, 14] },
            { table: source, layout: {
                fillColor: index => index === 0 ? green : index % 2 === 0 ? '#f3f7f4' : '#ffffff',
                hLineWidth: () => .5, vLineWidth: () => 0, hLineColor: () => border,
                paddingLeft: () => 8, paddingRight: () => 8, paddingTop: () => 9, paddingBottom: () => 9
            } },
            ...(meta.count === 0 ? [{ text: 'No hay programaciones para los filtros seleccionados.', color: muted, margin: [0, 12, 0, 0] }] : []),
            { text: 'Reporte de recorridos registrados en el Sistema de Gestión de Residuos Sólidos.', color: muted, italics: true, fontSize: 8, margin: [0, 14, 0, 0] }
        ];
        doc.footer = (page, pages) => ({ margin: [36, 12, 36, 0], columns: [
            { text: 'SGRS JLO  /  Programaciones', color: muted, fontSize: 8 },
            { text: `Página ${page} de ${pages}`, color: muted, alignment: 'right', fontSize: 8 }
        ] });
    }

    function customizePrint(win, meta, data) {
        win.document.title = 'Programaciones | SGRS JLO';
        // Replace inherited app styles so light/dark theme cannot change paper output.
        win.document.querySelectorAll('link[rel="stylesheet"], style').forEach(node => node.remove());
        const style = win.document.createElement('style');
        style.textContent = `
            @page { size: A4 landscape; margin: 14mm; }
            * { box-sizing: border-box; }
            html, body { margin: 0; padding: 0; background: #fff !important; color: ${ink} !important; color-scheme: light; }
            body { font: 10pt/1.45 Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .report { max-width: 1120px; margin: 24px auto; }
            .report-header { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 18px; border-bottom: 3px solid ${green}; }
            .brand { color: ${green}; font-size: 18pt; font-weight: 700; letter-spacing: -.5px; }
            .brand-note, .report-issued { color: ${muted}; font-size: 9pt; }
            .report-issued { text-align: right; }
            .report-issued strong { display: block; color: ${green}; font-size: 8pt; letter-spacing: 1px; margin-bottom: 5px; }
            h1 { margin: 22px 0 2px; font-size: 27pt; letter-spacing: -.8px; line-height: 1.2; }
            .subtitle { margin: 0 0 20px; color: ${muted}; font-size: 11pt; }
            .report-summary { display: flex; justify-content: space-between; gap: 12px; padding: 11px 14px; background: #f3f7f4; border: 1px solid ${border}; border-radius: 6px; color: ${muted}; }
            .report-summary strong { color: ${green}; }
            .report-filters { margin: 12px 0 17px; color: ${muted}; font-size: 9pt; overflow-wrap: anywhere; }
            table { width: 100%; border-collapse: collapse; table-layout: auto; font-size: 9pt; }
            thead { display: table-header-group; }
            th { padding: 11px 10px; text-align: left; background: ${green} !important; color: #fff !important; font-size: 8.5pt; font-weight: 600; }
            td { padding: 12px 10px; border-bottom: 1px solid ${border}; vertical-align: top; white-space: pre-line; overflow-wrap: anywhere; }
            tr:nth-child(even) td { background: #f3f7f4 !important; }
            tr { break-inside: avoid; page-break-inside: avoid; }
            .report-note { color: ${muted}; font-size: 8.5pt; margin: 16px 0 20px; }
            .report-footer { border-top: 1px solid ${border}; padding-top: 10px; font-size: 8pt; color: ${muted}; }
            @media print { .report { max-width: none; margin: 0; } a { color: inherit; text-decoration: none; } }
        `;
        win.document.head.appendChild(style);
        const rows = data.body.map(row => `<tr>${row.map(cell => `<td>${html(cell)}</td>`).join('')}</tr>`).join('');
        win.document.body.className = '';
        win.document.body.innerHTML = `<main class="report">
            <header class="report-header"><div><div class="brand">SGRS JLO</div><div class="brand-note">Sistema de Gestión de Residuos Sólidos</div></div><div class="report-issued"><strong>REPORTE DE PROGRAMACIONES</strong>Emitido: ${html(meta.issued)}</div></header>
            <h1>Programaciones</h1><p class="subtitle">Planificación de vehículos y personal</p>
            <div class="report-summary"><strong>${meta.count} registros en el reporte</strong><span>${meta.scheduled} programados / ${meta.finished} finalizados</span></div>
            <p class="report-filters">${meta.filters.map(html).join(' &nbsp; | &nbsp; ')}</p>
            <table><thead><tr>${data.header.map(cell => `<th scope="col">${html(cell)}</th>`).join('')}</tr></thead><tbody>${rows || `<tr><td colspan="${data.header.length}">No hay programaciones para los filtros seleccionados.</td></tr>`}</tbody></table>
            <p class="report-note">Reporte de recorridos registrados en el Sistema de Gestión de Residuos Sólidos.</p>
            <footer class="report-footer">SGRS JLO / Programaciones</footer>
        </main>`;
    }
    return { customizePdf, customizePrint };
})();
