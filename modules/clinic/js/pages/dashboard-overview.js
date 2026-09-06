(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, function (ch) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#039;',
                '"': '&quot;'
            }[ch];
        });
    }

    function setupHiDPICanvas(canvas) {
        if (!canvas || !canvas.getContext) return canvas;
        var dpr = Math.max(1, window.devicePixelRatio || 1);
        var ctx = canvas.getContext('2d');
        var cssWidth = canvas.clientWidth;
        var cssHeight = canvas.clientHeight;
        if (!cssWidth || !cssHeight) {
            canvas.width = 1;
            canvas.height = 1;
            return canvas;
        }
        canvas.width = Math.round(cssWidth * dpr);
        canvas.height = Math.round(cssHeight * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        return canvas;
    }

    function roundRectPath(ctx, x, y, w, h, r) {
        var radius = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.arcTo(x + w, y, x + w, y + h, radius);
        ctx.arcTo(x + w, y + h, x, y + h, radius);
        ctx.arcTo(x, y + h, x, y, radius);
        ctx.arcTo(x, y, x + w, y, radius);
        ctx.closePath();
    }

    function drawVisitChart(canvas, rawVisits) {
        if (!canvas || !canvas.getContext) return;
        setupHiDPICanvas(canvas);
        var ctx = canvas.getContext('2d');
        var W = canvas.clientWidth;
        var H = canvas.clientHeight;
        ctx.clearRect(0, 0, W, H);

        var entries = Object.entries(rawVisits || {}).sort(function (a, b) {
            return a[0].localeCompare(b[0]);
        });
        if (entries.length === 0) entries = [['', 0]];

        var padL = 46, padR = 20, padT = 18, padB = 38;
        var chartW = Math.max(10, W - padL - padR);
        var chartH = Math.max(10, H - padT - padB);

        var values = entries.map(function (e) { return Number(e[1] || 0); });
        var maxVal = Math.max.apply(null, values.concat([1]));
        var niceMax = maxVal < 5 ? 5 : (maxVal < 10 ? 10 : (Math.ceil(maxVal / 5) * 5));

        var ySteps = 4;
        ctx.font = '11px "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
        ctx.fillStyle = '#94a3b8';
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.18)';
        ctx.lineWidth = 1;
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        for (var i = 0; i <= ySteps; i++) {
            var v = Math.round((niceMax / ySteps) * i);
            var y = padT + chartH - (v / niceMax) * chartH;
            ctx.fillText(String(v), padL - 10, y);
            ctx.beginPath();
            ctx.moveTo(padL, y);
            ctx.lineTo(padL + chartW, y);
            ctx.stroke();
        }

        var n = entries.length;
        var stepX = n > 1 ? chartW / (n - 1) : chartW;
        var points = entries.map(function (entry, idx) {
            var x = padL + (n > 1 ? idx * stepX : chartW / 2);
            var y = padT + chartH - (Number(entry[1] || 0) / niceMax) * chartH;
            return { x: x, y: y, label: entry[0], value: Number(entry[1] || 0) };
        });

        var lineColor1 = 'rgba(81, 70, 183, 1)';
        var lineColor2 = 'rgba(32, 0, 130, 1)';
        var fillGrad = ctx.createLinearGradient(0, padT, 0, padT + chartH);
        fillGrad.addColorStop(0, 'rgba(81, 70, 183, 0.28)');
        fillGrad.addColorStop(1, 'rgba(32, 0, 130, 0.02)');

        ctx.beginPath();
        points.forEach(function (p, i) {
            if (i === 0) ctx.moveTo(p.x, padT + chartH);
            ctx.lineTo(p.x, p.y);
        });
        if (points.length > 0) ctx.lineTo(points[points.length - 1].x, padT + chartH);
        ctx.closePath();
        ctx.fillStyle = fillGrad;
        ctx.fill();

        ctx.beginPath();
        points.forEach(function (p, i) {
            if (i === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        var lineGrad = ctx.createLinearGradient(padL, 0, padL + chartW, 0);
        lineGrad.addColorStop(0, lineColor1);
        lineGrad.addColorStop(1, lineColor2);
        ctx.strokeStyle = lineGrad;
        ctx.lineWidth = 3;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.stroke();

        points.forEach(function (p) {
            ctx.beginPath();
            ctx.arc(p.x, p.y, 4.5, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.beginPath();
            ctx.arc(p.x, p.y, 2.8, 0, Math.PI * 2);
            var dotGrad = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, 3);
            dotGrad.addColorStop(0, lineColor1);
            dotGrad.addColorStop(1, lineColor2);
            ctx.fillStyle = dotGrad;
            ctx.fill();
        });

        ctx.fillStyle = '#64748b';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        var labelMod = Math.ceil(n / 7);
        points.forEach(function (p, i) {
            if (i % labelMod !== 0 && i !== n - 1) return;
            var parts = String(p.label).split('-');
            var shortLabel = parts.length === 3 ? (parts[1] + '/' + parts[2]) : String(p.label).slice(5);
            ctx.fillText(shortLabel, p.x, padT + chartH + 10);
        });
    }

    function drawInventoryDonut(canvas, inventory) {
        if (!canvas || !canvas.getContext) return;
        setupHiDPICanvas(canvas);
        var ctx = canvas.getContext('2d');
        var W = canvas.clientWidth;
        var H = canvas.clientHeight;
        ctx.clearRect(0, 0, W, H);

        var segments = [
            { label: 'In Stock', value: Number((inventory && inventory.in_stock) || 0), color1: '#34d399', color2: '#10b981' },
            { label: 'Low Stock', value: Number((inventory && inventory.low_stock) || 0), color1: '#fbbf24', color2: '#f59e0b' },
            { label: 'Expired', value: Number((inventory && inventory.expired) || 0), color1: '#f87171', color2: '#ef4444' }
        ];
        var total = segments.reduce(function (s, seg) { return s + seg.value; }, 0);
        if (total <= 0) { segments[0].value = 1; total = 1; }

        var cx = W / 2;
        var cy = H / 2;
        var outerR = Math.min(cx, cy) - 6;
        var innerR = outerR * 0.63;
        var holeR = innerR - 2;

        ctx.beginPath();
        ctx.arc(cx, cy, outerR + 2, 0, Math.PI * 2);
        ctx.arc(cx, cy, holeR, 0, Math.PI * 2, true);
        var shadowGrad = ctx.createRadialGradient(cx, cy, innerR, cx, cy, outerR + 14);
        shadowGrad.addColorStop(0, 'rgba(15, 23, 42, 0.02)');
        shadowGrad.addColorStop(1, 'rgba(15, 23, 42, 0.18)');
        ctx.fillStyle = shadowGrad;
        ctx.fill('evenodd');

        var start = -Math.PI / 2;
        segments.forEach(function (seg) {
            if (seg.value <= 0) return;
            var frac = seg.value / total;
            var end = start + frac * Math.PI * 2;
            ctx.beginPath();
            ctx.arc(cx, cy, outerR, start, end, false);
            ctx.arc(cx, cy, innerR, end, start, true);
            ctx.closePath();
            var segGrad = ctx.createLinearGradient(cx - outerR, cy - outerR, cx + outerR, cy + outerR);
            segGrad.addColorStop(0, seg.color1);
            segGrad.addColorStop(1, seg.color2);
            ctx.fillStyle = segGrad;
            ctx.fill();
            start = end;
        });

        ctx.beginPath();
        ctx.arc(cx, cy, innerR - 1, 0, Math.PI * 2);
        ctx.arc(cx, cy, outerR + 1, 0, Math.PI * 2, true);
        ctx.fillStyle = 'rgba(255,255,255,0.001)';
        ctx.fill('evenodd');
    }

    function drawDualLineChart(canvas, workforce) {
        if (!canvas || !canvas.getContext) return;
        setupHiDPICanvas(canvas);
        var ctx = canvas.getContext('2d');
        var W = canvas.clientWidth;
        var H = canvas.clientHeight;
        ctx.clearRect(0, 0, W, H);

        var entries = Object.entries(workforce || {}).sort(function (a, b) { return a[0].localeCompare(b[0]); });
        if (entries.length === 0) entries = [['', { visits: 0, hires: 0 }]];

        var padL = 46, padR = 22, padT = 22, padB = 38;
        var chartW = Math.max(10, W - padL - padR);
        var chartH = Math.max(10, H - padT - padB);
        var values = [];
        entries.forEach(function (e) {
            values.push(Number((e[1] && e[1].visits) || 0));
            values.push(Number((e[1] && e[1].hires) || 0));
        });
        var maxVal = Math.max.apply(null, values.concat([1]));
        var niceMax = maxVal < 5 ? 5 : (maxVal < 10 ? 10 : (Math.ceil(maxVal / 5) * 5));

        var ySteps = 4;
        ctx.font = '11px "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
        ctx.fillStyle = '#94a3b8';
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.18)';
        ctx.lineWidth = 1;
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        for (var i = 0; i <= ySteps; i++) {
            var v = Math.round((niceMax / ySteps) * i);
            var y = padT + chartH - (v / niceMax) * chartH;
            ctx.fillText(String(v), padL - 10, y);
            ctx.beginPath();
            ctx.moveTo(padL, y);
            ctx.lineTo(padL + chartW, y);
            ctx.stroke();
        }

        var n = entries.length;
        var stepX = n > 1 ? chartW / (n - 1) : chartW;
        function pointsFor(key) {
            return entries.map(function (entry, idx) {
                var v = Number((entry[1] && entry[1][key]) || 0);
                var x = padL + (n > 1 ? idx * stepX : chartW / 2);
                var y = padT + chartH - (v / niceMax) * chartH;
                return { x: x, y: y, value: v, label: entry[0] };
            });
        }
        var visits = pointsFor('visits');
        var hires = pointsFor('hires');

        function drawBand(pts, c1, c2, alpha) {
            if (pts.length === 0) return;
            ctx.beginPath();
            pts.forEach(function (p, i) {
                if (i === 0) ctx.moveTo(p.x, padT + chartH);
                ctx.lineTo(p.x, p.y);
            });
            ctx.lineTo(pts[pts.length - 1].x, padT + chartH);
            ctx.closePath();
            var grad = ctx.createLinearGradient(0, padT, 0, padT + chartH);
            grad.addColorStop(0, 'rgba(' + c1 + ',' + (alpha) + ')');
            grad.addColorStop(1, 'rgba(' + c2 + ',0.02)');
            ctx.fillStyle = grad;
            ctx.fill();
        }

        drawBand(visits, '16, 185, 129', '5, 150, 105', 0.26);
        drawBand(hires, '59, 130, 246', '37, 99, 235', 0.16);

        function drawLine(pts, c1, c2, dashed) {
            ctx.beginPath();
            pts.forEach(function (p, i) { if (i === 0) ctx.moveTo(p.x, p.y); else ctx.lineTo(p.x, p.y); });
            var grad = ctx.createLinearGradient(padL, 0, padL + chartW, 0);
            grad.addColorStop(0, c1);
            grad.addColorStop(1, c2);
            ctx.strokeStyle = grad;
            ctx.lineWidth = 3;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            if (dashed) ctx.setLineDash([6, 5]); else ctx.setLineDash([]);
            ctx.stroke();
            ctx.setLineDash([]);
        }

        drawLine(visits, '#10b981', '#047857', false);
        drawLine(hires, '#3b82f6', '#1d4ed8', true);

        function drawDots(pts, color) {
            pts.forEach(function (p) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.fill();
                ctx.beginPath();
                ctx.arc(p.x, p.y, 2.4, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
            });
        }
        drawDots(visits, '#047857');
        drawDots(hires, '#1d4ed8');

        ctx.fillStyle = '#64748b';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        var labelMod = Math.ceil(n / 7);
        visits.forEach(function (p, i) {
            if (i % labelMod !== 0 && i !== n - 1) return;
            var parts = String(p.label).split('-');
            var shortLabel = parts.length === 3 ? (parts[1] + '/' + parts[2]) : String(p.label).slice(5);
            ctx.fillText(shortLabel, p.x, padT + chartH + 10);
        });
    }

    function drawDonutChart(canvas, depts) {
        if (!canvas || !canvas.getContext) return;
        setupHiDPICanvas(canvas);
        var ctx = canvas.getContext('2d');
        var W = canvas.clientWidth;
        var H = canvas.clientHeight;
        ctx.clearRect(0, 0, W, H);
        var palette = [
            ['#818cf8', '#6366f1'],
            ['#34d399', '#10b981'],
            ['#fbbf24', '#f59e0b'],
            ['#f87171', '#ef4444'],
            ['#60a5fa', '#3b82f6'],
            ['#a78bfa', '#7c3aed'],
            ['#f472b6', '#ec4899'],
            ['#fb923c', '#f97316']
        ];
        var data = (depts || []).map(function (d, i) {
            var p = palette[i % palette.length];
            return { label: d.label || 'Department ' + (i + 1), value: Number(d.value || 0), color1: p[0], color2: p[1] };
        }).filter(function (d) { return d.value > 0; });
        var total = data.reduce(function (s, d) { return s + d.value; }, 0);
        if (total <= 0) {
            data = [{ label: 'No Data', value: 1, color1: '#cbd5e1', color2: '#94a3b8' }];
            total = 1;
        }
        var cx = W / 2;
        var cy = H / 2;
        var outerR = Math.min(cx, cy) - 6;
        var innerR = outerR * 0.63;
        var start = -Math.PI / 2;
        data.forEach(function (d) {
            if (d.value <= 0) return;
            var end = start + (d.value / total) * Math.PI * 2;
            ctx.beginPath();
            ctx.arc(cx, cy, outerR, start, end, false);
            ctx.arc(cx, cy, innerR, end, start, true);
            ctx.closePath();
            var g = ctx.createLinearGradient(cx - outerR, cy - outerR, cx + outerR, cy + outerR);
            g.addColorStop(0, d.color1);
            g.addColorStop(1, d.color2);
            ctx.fillStyle = g;
            ctx.fill();
            start = end;
        });
    }

    function drawDeptDonut(canvas, depts) {
        drawDonutChart(canvas, depts);
    }

    function animateCountUp(el, target) {
        if (!el) return;
        var start = Date.now();
        var from = 0;
        var duration = 700;
        function step() {
            var t = Math.min(1, (Date.now() - start) / duration);
            var eased = 1 - Math.pow(1 - t, 3);
            var val = Math.round(from + (Number(target || 0) - from) * eased);
            el.textContent = val.toLocaleString();
            if (t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function applyDataToDOM(data) {
        var metrics = data.metrics || {};
        Object.entries(metrics).forEach(function (entry) {
            var key = entry[0];
            var value = entry[1];
            if (key.endsWith('_change')) return;
            var metricEl = document.querySelector('[data-metric="' + key + '"]');
            if (metricEl) animateCountUp(metricEl, value);
            var changeEl = document.querySelector('[data-change="' + key + '_change"]');
            if (changeEl) {
                var pct = Number(metrics[key + '_change'] ?? 0);
                var abs = Math.abs(pct);
                changeEl.querySelector('span') && (changeEl.querySelector('span').textContent = abs + '%');
                changeEl.classList.remove('stat-change--up', 'stat-change--down');
                changeEl.classList.add(pct >= 0 ? 'stat-change--up' : 'stat-change--down');
                var icon = changeEl.querySelector('i');
                if (icon) {
                    icon.className = pct >= 0 ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down';
                }
            }
        });
        var vs = data.visit_stats || {};
        ['total', 'average_day', 'label', 'total_change', 'average_change'].forEach(function (k) {
            var el = document.querySelector('[data-visit-stat="' + k + '"]');
            if (el) {
                if (k === 'total' || k === 'average_day') animateCountUp(el, vs[k] ?? 0);
                else el.textContent = String(vs[k] ?? '');
            }
        });
        ['total_change', 'average_change'].forEach(function (k) {
            var changeEl = document.querySelector('[data-change="' + k + '"]');
            if (!changeEl) return;
            var pct = Number(vs[k] ?? 0);
            var abs = Math.abs(pct);
            var span = changeEl.querySelector('span');
            if (span) span.textContent = abs + '%';
            changeEl.classList.remove('stat-change--up', 'stat-change--down');
            changeEl.classList.add(pct >= 0 ? 'stat-change--up' : 'stat-change--down');
            var icon = changeEl.querySelector('i');
            if (icon) icon.className = pct >= 0 ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down';
        });

        var inv = data.inventory_status || {};
        var invTotal = Math.max(1, Number(inv.total || 0));
        var invTotalEl = document.getElementById('inventoryTotal');
        if (invTotalEl) animateCountUp(invTotalEl, invTotal);
        function updateInv(id, val) {
            var el = document.getElementById(id);
            if (!el) return;
            var num = Number(val || 0);
            var pct = Math.round((num / invTotal) * 100);
            el.childNodes.forEach(function (node) {
                if (node.nodeType === 3) node.nodeValue = num + ' ';
            });
            var small = el.querySelector('small');
            if (small) small.textContent = '(' + pct + '%)';
        }
        updateInv('inv-instock', inv.in_stock);
        updateInv('inv-lowstock', inv.low_stock);
        updateInv('inv-expired', inv.expired);
    }

    function renderCharts(data) {
        var visitChart = document.getElementById('visitChart');
        var deptDonut = document.getElementById('deptDonut');
        var invDonut = document.getElementById('inventoryDonut');

        if (visitChart && data.patient_visits) {
            drawVisitChart(visitChart, data.patient_visits);
        } else if (visitChart && data.workforce_visits) {
            drawDualLineChart(visitChart, data.workforce_visits);
        }

        if (deptDonut && (data.departments || []).length) {
            drawDeptDonut(deptDonut, data.departments);
        }

        if (invDonut && data.inventory_status) {
            drawInventoryDonut(invDonut, data.inventory_status);
        }
    }

    function renderLists(data) {
        var appts = data.upcoming_appointments || [];
        var apptList = document.getElementById('appointmentList');
        if (apptList) {
            if (appts.length === 0) {
                apptList.innerHTML = '<li class="empty-dash"><i class="fa-regular fa-calendar-xmark"></i> No upcoming appointments.</li>';
            } else {
                apptList.innerHTML = appts.map(function (a) {
                    try {
                        var timeStr = new Date('1970-01-01T' + (a.appointment_time || '00:00:00')).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                    } catch (e) { timeStr = String(a.appointment_time || ''); }
                    return '<li class="appointment-item">' +
                        '<span class="appointment-time">' + escapeHtml(timeStr) + '</span>' +
                        '<span class="appointment-name">' + escapeHtml(a.patient_name || 'Unknown patient') + '</span>' +
                        '<span class="appointment-purpose">' + escapeHtml(a.purpose || '') + '</span>' +
                        '<span class="appointment-badge">' + escapeHtml(a.status || '') + '</span>' +
                        '</li>';
                }).join('');
            }
        }

        var ems = data.recent_emergency || [];
        var emList = document.getElementById('emergencyList');
        if (emList) {
            if (ems.length === 0) {
                emList.innerHTML = '<li class="empty-dash"><i class="fa-solid fa-shield-heart"></i> No recent emergency cases.</li>';
            } else {
                emList.innerHTML = ems.map(function (e) {
                    var sev = String(e.severity_level || 'Medium').toLowerCase();
                    var sevClass = 'urgent-tag';
                    if (sev === 'high' || sev === 'critical' || sev === 'urgent') sevClass += ' urgent-tag--critical';
                    else if (sev === 'low' || sev === 'minor') sevClass += ' urgent-tag--low';
                    var timeStr = '';
                    try { timeStr = new Date(e.incident_date || Date.now()).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); } catch (_) {}
                    return '<li class="emergency-item">' +
                        '<span class="' + sevClass + '">' + escapeHtml(e.severity_level || 'Medium') + '</span>' +
                        '<div class="emergency-info">' +
                            '<strong>' + escapeHtml(e.patient_name || 'Unknown patient') + '</strong>' +
                            '<small>' + escapeHtml(e.chief_complaint || '') + '</small>' +
                        '</div>' +
                        '<span class="emergency-time">' + escapeHtml(timeStr) + '</span>' +
                        '</li>';
                }).join('');
            }
        }

        var reps = data.recent_reports || [];
        var repList = document.getElementById('reportsList');
        if (repList) {
            if (reps.length === 0) {
                repList.innerHTML = '<li class="empty-dash"><i class="fa-regular fa-folder-open"></i> No reports available.</li>';
            } else {
                repList.innerHTML = reps.map(function (r) {
                    var dateStr = '';
                    try { dateStr = new Date(r.report_date || Date.now()).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }); } catch (_) {}
                    return '<li class="reports-item">' +
                        '<span class="reports-icon"><i class="fa-regular fa-file-lines"></i></span>' +
                        '<div class="reports-info"><strong>' + escapeHtml(r.report_type || 'Report') + ' Clinic Report - ' + escapeHtml(dateStr) + '</strong></div>' +
                        '<span class="reports-date">' + escapeHtml(dateStr) + '</span>' +
                        '</li>';
                }).join('');
            }
        }
    }

    function renderEmployeeLists(data) {
        var recTable = document.getElementById('recentMedicalTableBody');
        if (recTable && Array.isArray(data.recent_medical_records)) {
            if (data.recent_medical_records.length === 0) {
                recTable.innerHTML = '<tr><td colspan="6" class="empty-clinic">No recent medical records.</td></tr>';
            } else {
                recTable.innerHTML = data.recent_medical_records.map(function (r) {
                    var typeClass = String(r.consultation_type || 'general').toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                    var statusClass = String(r.status || 'pending').toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                    var dateStr = '';
                    try { dateStr = new Date(r.visit_date || Date.now()).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }); } catch (_) {}
                    var empCodeHtml = r.employee_code
                        ? '<span class="emp-code"><i class="fa-regular fa-id-badge"></i> ' + escapeHtml(r.employee_code) + '</span>'
                        : '';
                    return '<tr class="employee-row" data-employee-id="' + Number(r.employee_id || 0) + '">' +
                        '<td><div class="emp-name-wrap"><strong class="emp-name">' + escapeHtml(r.employee_name || 'Unknown Employee') + '</strong>' + empCodeHtml + '</div></td>' +
                        '<td>' + escapeHtml(r.department_name || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(dateStr) + '</td>' +
                        '<td>' + escapeHtml(r.chief_complaint || 'N/A') + '</td>' +
                        '<td><span class="clinic-badge ' + typeClass + '">' + escapeHtml(r.consultation_type || 'N/A') + '</span></td>' +
                        '<td><span class="clinic-badge ' + statusClass + '">' + escapeHtml(r.status || 'N/A') + '</span></td>' +
                        '</tr>';
                }).join('');
                hookRecentRecordsRowClicks();
            }
        }
    }

    function renderEmployeeTable(data) {
        var tbody = document.getElementById('employeeTableBody');
        if (!tbody) return;
        var items = data.items || [];
        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-clinic">No employees found. Try adjusting search or filters.</td></tr>';
        } else {
            tbody.innerHTML = items.map(function (emp) {
                var status = String(emp.employment_status || 'N/A');
                var statusLow = status.toLowerCase();
                var isActive = statusLow === 'active';
                var statusBadge = '<span class="clinic-badge ' + (isActive ? 'active' : 'inactive') + '">' + escapeHtml(status) + '</span>';
                var records = Number(emp.medical_record_count || 0);
                var recordsBadge = records > 0
                    ? '<span class="clinic-badge active">' + records + ' record' + (records !== 1 ? 's' : '') + '</span>'
                    : '<span class="clinic-badge">0 records</span>';
                var lastVisit = emp.last_visit_date ? new Date(emp.last_visit_date).toLocaleDateString() : '\u2014';
                var empId = Number(emp.employee_id || 0);
                var viewBtn = '<a href="?page=medical-records-history&employee_id=' + empId + '" class="btn-view" title="View Medical Records"><i class="fa-regular fa-eye"></i> <span>View</span></a>';
                var fullName = escapeHtml(emp.full_name || ((emp.first_name || '') + ' ' + (emp.last_name || '')));
                var codeCell = emp.employee_code ? escapeHtml(emp.employee_code) : String(empId);
                var empCodeHtml = emp.employee_code
                    ? '<span class="emp-code"><i class="fa-regular fa-id-badge"></i> ' + escapeHtml(emp.employee_code) + '</span>'
                    : '';
                var nameClass = 'emp-name' + (isActive ? '' : ' status-inactive');
                return '<tr class="employee-row" data-employee-id="' + empId + '">' +
                    '<td><strong>' + codeCell + '</strong></td>' +
                    '<td><div class="emp-name-wrap"><strong class="' + nameClass + '">' + fullName + '</strong>' + empCodeHtml + '</div></td>' +
                    '<td>' + escapeHtml(emp.department_name || 'N/A') + '</td>' +
                    '<td>' + escapeHtml(emp.position_name || 'N/A') + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td>' + recordsBadge + '</td>' +
                    '<td>' + escapeHtml(lastVisit) + '</td>' +
                    '<td>' + viewBtn + '</td></tr>';
            }).join('');
        }
    }

    function hookRecentRecordsRowClicks() {
        document.querySelectorAll('#recentMedicalTableBody .employee-row').forEach(function (row) {
            row.addEventListener('click', function (e) {
                var id = Number(row.getAttribute('data-employee-id') || 0);
                if (!id) return;
                var target = e.target;
                while (target && target !== row) {
                    if (target.tagName === 'A' || target.tagName === 'BUTTON') return;
                    target = target.parentElement;
                }
                window.location.href = '?page=medical-records-history&employee_id=' + id;
            });
            row.style.cursor = 'pointer';
        });
    }

    function hookStatCardClicks() {
        var grid = document.getElementById('statGrid');
        if (!grid) return;
        grid.querySelectorAll('article.stat-card').forEach(function (card) {
            var cls = card.className || '';
            var dest = null;
            if (cls.indexOf('stat--blue') !== -1) dest = '?page=medical-records-history';
            else if (cls.indexOf('stat--green') !== -1) dest = '?page=medical-records-history';
            else if (cls.indexOf('stat--purple') !== -1) dest = '?page=emergency-cases';
            else if (cls.indexOf('stat--amber') !== -1) dest = '?page=medicines-inventory';
            else if (cls.indexOf('stat--red') !== -1) dest = '?page=medicines-inventory';
            if (!dest) return;
            card.setAttribute('role', 'link');
            card.setAttribute('tabindex', '0');
            card.style.cursor = 'pointer';
            var go = function () { window.location.href = dest; };
            card.addEventListener('click', go);
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
            });
        });
    }

    function hookPanelClicks() {
        document.querySelectorAll('.employee-directory-table .employee-row').forEach(function (row) {
            row.addEventListener('click', function (e) {
                var target = e.target;
                while (target && target !== row) {
                    if (target.tagName === 'A' || target.tagName === 'BUTTON') return;
                    target = target.parentElement;
                }
                var id = Number(row.getAttribute('data-employee-id') || 0);
                if (id) window.location.href = '?page=medical-records-history&employee_id=' + id;
            });
        });
    }

    function loadDepartments(cb) {
        var sel = document.getElementById('deptFilter');
        if (!sel) return cb();
        if (sel.dataset.loaded === '1') return cb();
        fetch('?page=employee-list&action=departments', { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.text() : Promise.reject(new Error('HTTP ' + r.status)); })
            .then(function (raw) {
                try {
                    if (raw.trim().startsWith('<')) throw new Error('HTML detected');
                    var opts = JSON.parse(raw);
                    sel.innerHTML = '<option value="">All Departments</option>' +
                        opts.map(function (d) { return '<option value="' + escapeHtml(d.value || d.department_code || '') + '">' + escapeHtml(d.label || d.department_name || '') + '</option>'; }).join('');
                    sel.dataset.loaded = '1';
                } catch (_) { /* ignore */ }
                cb();
            })
            .catch(function () { cb(); });
    }

    var employeeXHR = null;

    function resolveDataUrl(fileName) {
        var base = document.currentScript && document.currentScript.src
            ? document.currentScript.src.replace(/\/js\/pages\/[^/]+\.js.*$/, '/' + fileName)
            : null;
        if (base) return base;
        var pathname = location.pathname;
        var idx = pathname.lastIndexOf('/modules/clinic/');
        if (idx >= 0) return pathname.slice(0, idx) + '/modules/clinic/' + fileName;
        return fileName;
    }
    function loadEmployees(resetPage) {
        var searchBox = document.getElementById('employeeSearch');
        var statusSel = document.getElementById('statusFilter');
        var deptSel = document.getElementById('deptFilter');
        var sortSel = document.getElementById('sortBy');
        var pageInfo = document.getElementById('employeePageInfo');
        var pageLinks = document.getElementById('employeePageLinks');
        var page = Number(document.getElementById('employeeCurrentPage')?.value || 1);
        if (resetPage) { page = 1; var input = document.getElementById('employeeCurrentPage'); if (input) input.value = '1'; }
        var params = new URLSearchParams();
        params.set('page', 'employee-list');
        params.set('action', 'search');
        if (searchBox) params.set('q', searchBox.value || '');
        if (statusSel && statusSel.value) params.set('status', statusSel.value);
        if (deptSel && deptSel.value) params.set('department', deptSel.value);
        if (sortSel && sortSel.value) params.set('sort', sortSel.value);
        params.set('p', String(page));
        if (employeeXHR && employeeXHR.cancel) employeeXHR.cancel();
        var controller = 'AbortController' in window ? new AbortController() : null;
        employeeXHR = controller ? { cancel: function () { controller.abort(); } } : null;
        fetch('index.php?' + params.toString(), {
            credentials: 'same-origin',
            cache: 'no-store',
            signal: controller ? controller.signal : undefined
        }).then(function (r) { return r.ok ? r.text() : Promise.reject(new Error('HTTP ' + r.status)); })
          .then(function (raw) {
              var trimmed = (raw || '').trim();
              if (trimmed.startsWith('<')) throw new Error('HTML detected');
              var data;
              try { data = JSON.parse(trimmed); } catch (e) {
                  var p = trimmed.slice(0, 400);
                  console.error('[Dashboard Employee Search] parse failed:', p, e);
                  data = { items: [], total: 0, page: 1, total_pages: 1 };
              }
              renderEmployeeTable(data);
              var total = Number(data.total || 0);
              var page2 = Number(data.page || 1);
              var perPage = Number(data.per_page || 10);
              var totalPages = Math.max(1, Number(data.total_pages || 1));
              var start = total === 0 ? 0 : (page2 - 1) * perPage + 1;
              var end = Math.min(total, page2 * perPage);
              if (pageInfo) pageInfo.textContent = 'Showing ' + start.toLocaleString() + '\u2013' + end.toLocaleString() + ' of ' + total.toLocaleString();
              if (pageLinks) {
                  var html = '';
                  for (var i = 1; i <= totalPages; i++) {
                      html += '<button type="button" data-page="' + i + '" class="page-btn' + (i === page2 ? ' active' : '') + '">' + i + '</button>';
                  }
                  pageLinks.innerHTML = html;
                  pageLinks.querySelectorAll('button.page-btn').forEach(function (btn) {
                      btn.addEventListener('click', function () {
                          var targetPage = Number(btn.getAttribute('data-page') || 1);
                          var curr = document.getElementById('employeeCurrentPage');
                          if (curr) curr.value = String(targetPage);
                          loadEmployees(false);
                      });
                  });
              }
              hookPanelClicks();
          }).catch(function (err) {
              if (err && err.name === 'AbortError') return;
              if (pageInfo) pageInfo.textContent = 'Unable to load employee directory.';
              if (pageLinks) pageLinks.innerHTML = '';
              console.error('[Dashboard Employee Search] error:', err);
          });
    }

    function hookEmployeeFilters() {
        var searchBox = document.getElementById('employeeSearch');
        var statusSel = document.getElementById('statusFilter');
        var deptSel = document.getElementById('deptFilter');
        var sortSel = document.getElementById('sortBy');
        var searchTimer = null;
        function scheduleSearch() {
            if (searchTimer) clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadEmployees(true); }, 280);
        }
        if (searchBox) searchBox.addEventListener('input', scheduleSearch);
        if (statusSel) statusSel.addEventListener('change', function () { loadEmployees(true); });
        if (deptSel) deptSel.addEventListener('change', function () { loadEmployees(true); });
        if (sortSel) sortSel.addEventListener('change', function () { loadEmployees(false); });
    }

    function loadAndRenderDashboard(range) {
        var init = (typeof window.__DASH_INIT_DATA__ !== 'undefined') ? window.__DASH_INIT_DATA__ : null;
        if (!range && init) {
            applyDataToDOM(init);
            renderCharts(init);
            renderLists(init);
            if (document.getElementById('recentMedicalTableBody') || document.getElementById('employeeTableBody')) {
                renderEmployeeLists(init);
                if (document.getElementById('employeeTableBody')) {
                    loadDepartments(function () {
                        hookEmployeeFilters();
                        loadEmployees(true);
                    });
                }
            }
            return Promise.resolve(init);
        }
        var params = new URLSearchParams();
        if (range) params.set('range', range);
        var endpoint = resolveDataUrl('dashboard-data.php');
        return fetch(endpoint + (params.toString() ? ('?' + params.toString()) : ''), {
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        }).then(function (raw) {
            var trimmed = (raw || '').trim();
            if (trimmed.startsWith('<')) {
                console.error('[Dashboard] Got HTML instead of JSON. Preview:', trimmed.slice(0, 500));
                throw new Error('Temporary server issue \u2014 please refresh the page.');
            }
            var data;
            try {
                data = JSON.parse(trimmed);
            } catch (e) {
                console.error('[Dashboard] JSON parse failed:', e, 'preview:', trimmed.slice(0, 500));
                throw new Error('Invalid dashboard response.');
            }
            applyDataToDOM(data);
            renderCharts(data);
            renderLists(data);
            if (document.getElementById('recentMedicalTableBody') || document.getElementById('employeeTableBody')) {
                renderEmployeeLists(data);
            }
            return data;
        }).catch(function (err) {
            console.error('[Dashboard] load failed:', err);
        });
    }

    function hookRangeSelect() {
        var sel = document.getElementById('rangeSelect');
        if (!sel) return;
        if (sel.dataset.hooked === '1') return;
        sel.dataset.hooked = '1';
        sel.addEventListener('change', function () {
            var value = sel.value || 'this_month';
            loadAndRenderDashboard(value);
        });
    }

    function initDashboard() {
        var root = document.querySelector('.clinic-dashboard');
        if (!root) {
            if (initDashboard._retry === undefined) initDashboard._retry = 0;
            if (initDashboard._retry < 8) {
                initDashboard._retry += 1;
                setTimeout(initDashboard, 120 * initDashboard._retry);
            }
            return;
        }
        if (root.dataset.initialized === '1') return;
        root.dataset.initialized = '1';
        initDashboard._retry = 0;

        loadAndRenderDashboard(null);
        hookRangeSelect();
        hookStatCardClicks();
        if (document.getElementById('employeeTableBody')) {
            loadDepartments(function () {
                hookEmployeeFilters();
                loadEmployees(true);
            });
        } else if (document.getElementById('recentMedicalTableBody')) {
            hookRecentRecordsRowClicks();
        }

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (resizeTimer) clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                var data = (typeof window.__DASH_INIT_DATA__ !== 'undefined') ? window.__DASH_INIT_DATA__ : null;
                if (!data) return;
                renderCharts(data);
            }, 120);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboard);
    } else {
        initDashboard();
    }
    window.addEventListener('page:loaded', initDashboard);
})();
