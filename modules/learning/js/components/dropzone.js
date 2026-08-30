/**
 * DropZone - Reusable drag-and-drop file upload component
 * Usage: DropZone.init(element, options)
 * options: { url, fieldName, accept, multiple, onSuccess, onError, onProgress }
 */
var DropZone = (function() {
    var instances = {};

    function createMarkup(opts) {
        var accept = opts.accept || 'image/*';
        var label = opts.label || 'Drag & drop files here or click to browse';
        var sublabel = accept === 'image/*' ? 'Images only (JPG, PNG, GIF, WebP)' :
                       accept === 'video/*' ? 'Videos only (MP4, WebM, OGG)' :
                       accept === '.csv,.txt' ? 'CSV or TXT files' : 'Any file';

        var wrapper = document.createElement('div');
        wrapper.className = 'dz-wrapper';
        wrapper.innerHTML =
            '<div class="dz-zone">' +
                '<div class="dz-icon"><i class="fas fa-cloud-upload-alt"></i></div>' +
                '<div class="dz-label">' + label + '</div>' +
                '<div class="dz-sublabel">' + sublabel + '</div>' +
                '<div class="dz-browse">Browse Files</div>' +
                '<input type="file" class="dz-input" accept="' + accept + '" ' + (opts.multiple ? 'multiple' : '') + ' />' +
            '</div>' +
            '<div class="dz-preview" style="display:none;"></div>' +
            '<div class="dz-progress" style="display:none;">' +
                '<div class="dz-progress-bar"><div class="dz-progress-fill"></div></div>' +
                '<div class="dz-progress-text">0%</div>' +
            '</div>' +
            '<div class="dz-status" style="display:none;"></div>';
        return wrapper;
    }

    function init(el, opts) {
        if (typeof el === 'string') el = document.querySelector(el);
        if (!el) return null;

        opts = opts || {};
        var id = 'dz-' + Math.random().toString(36).substr(2, 9);
        var markup = createMarkup(opts);
        el.appendChild(markup);

        var zone = markup.querySelector('.dz-zone');
        var input = markup.querySelector('.dz-input');
        var preview = markup.querySelector('.dz-preview');
        var progress = markup.querySelector('.dz-progress');
        var progressFill = markup.querySelector('.dz-progress-fill');
        var progressText = markup.querySelector('.dz-progress-text');
        var status = markup.querySelector('.dz-status');
        var browse = markup.querySelector('.dz-browse');

        // Click to browse
        browse.addEventListener('click', function() { input.click(); });
        zone.addEventListener('click', function(e) {
            if (e.target === browse || e.target === zone || e.target.closest('.dz-icon') || e.target.closest('.dz-label') || e.target.closest('.dz-sublabel')) {
                input.click();
            }
        });

        // File selected
        input.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleFiles(this.files, opts);
            }
        });

        // Drag events
        zone.addEventListener('dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.add('dz-dragover');
        });
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.add('dz-dragover');
        });
        zone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('dz-dragover');
        });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('dz-dragover');
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                handleFiles(e.dataTransfer.files, opts);
            }
        });

        function handleFiles(files, opts) {
            for (var i = 0; i < files.length; i++) {
                uploadFile(files[i], opts);
            }
        }

        function uploadFile(file, opts) {
            // Show preview for images
            if (file.type && file.type.indexOf('image') === 0) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<div class="dz-thumb"><img src="' + e.target.result + '" /><span class="dz-filename">' + escapeHtml(file.name) + '</span><button class="dz-remove" title="Remove"><i class="fas fa-times"></i></button></div>';
                    preview.style.display = 'block';
                    preview.querySelector('.dz-remove').addEventListener('click', function() {
                        preview.style.display = 'none';
                        preview.innerHTML = '';
                        input.value = '';
                    });
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '<div class="dz-thumb"><div class="dz-file-icon"><i class="fas fa-file"></i></div><span class="dz-filename">' + escapeHtml(file.name) + '</span><span class="dz-filesize">' + formatSize(file.size) + '</span></div>';
                preview.style.display = 'block';
            }

            // Upload
            var url = opts.url;
            if (!url) {
                status.innerHTML = '<span style="color:#10b981;"><i class="fas fa-check-circle"></i> File selected: ' + escapeHtml(file.name) + '</span>';
                status.style.display = 'block';
                if (opts.onFileSelected) opts.onFileSelected(file);
                return;
            }

            var formData = new FormData();
            formData.append(opts.fieldName || 'file', file);

            var xhr = new XMLHttpRequest();
            progress.style.display = 'flex';

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var pct = Math.round((e.loaded / e.total) * 100);
                    progressFill.style.width = pct + '%';
                    progressText.textContent = pct + '%';
                }
            });

            xhr.addEventListener('load', function() {
                progress.style.display = 'none';
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        status.innerHTML = '<span style="color:#10b981;"><i class="fas fa-check-circle"></i> Uploaded: ' + escapeHtml(file.name) + '</span>';
                        if (opts.onSuccess) opts.onSuccess(data, file);
                    } catch (e) {
                        status.innerHTML = '<span style="color:#10b981;"><i class="fas fa-check-circle"></i> Uploaded: ' + escapeHtml(file.name) + '</span>';
                        if (opts.onSuccess) opts.onSuccess(null, file);
                    }
                } else {
                    status.innerHTML = '<span style="color:#ef4444;"><i class="fas fa-exclamation-circle"></i> Upload failed: ' + escapeHtml(file.name) + '</span>';
                    if (opts.onError) opts.onError(xhr.statusText, file);
                }
                status.style.display = 'block';
            });

            xhr.addEventListener('error', function() {
                progress.style.display = 'none';
                status.innerHTML = '<span style="color:#ef4444;"><i class="fas fa-exclamation-circle"></i> Network error uploading ' + escapeHtml(file.name) + '</span>';
                status.style.display = 'block';
                if (opts.onError) opts.onError('Network error', file);
            });

            xhr.open('POST', url);
            xhr.send(formData);
        }

        return { element: markup, input: input };
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function escapeHtml(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    return { init: init };
})();
