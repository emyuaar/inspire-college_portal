<x-app-layout page-title="Study Material Viewer" active-page="courses">

    <style>
        .pdf-page-wrapper {
            position: relative;
            display: inline-block;
            margin: 16px auto;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        #pdf-canvas {
            display: block;
            max-width: 100%;
            height: auto;
        }
    </style>

    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Top Bar / Back Link --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('portal.learner.course.show', $enrolment->id) }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-ds-navy transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Course
            </a>

            <div class="text-xs text-slate-400 font-medium">
                {{ $lesson->document_title ?? $lesson->title }}
            </div>
        </div>

        {{-- Viewer Card --}}
        <x-protected-content-guard 
            :learner-name="$user->name" 
            :learner-email="$user->email" 
            :course-name="$lesson->course->title ?? ''"
            :course-id="$lesson->course_id ?? null"
            :lesson-id="$lesson->id ?? null">
            <x-ui.card padding="p-0" class="overflow-hidden">

                {{-- Header --}}
                <div class="bg-slate-50 border-b border-slate-100 p-4 md:p-6 flex items-center justify-between">
                    <div>
                        <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 uppercase tracking-wide">
                            Online View
                        </span>
                        <h1 class="text-xl font-bold text-ds-navy mt-1">
                            {{ $lesson->document_title ?? $lesson->title }}
                        </h1>
                    </div>

                    {{-- Toolbar --}}
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 bg-slate-200/60 p-1 rounded-lg">
                            <button id="prev-page" class="p-1.5 rounded bg-white shadow-sm hover:bg-slate-50 disabled:opacity-50 viewer-control" disabled>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <span class="text-xs font-bold text-slate-600 px-2">
                                Page <span id="page-num">1</span> of <span id="page-count">-</span>
                            </span>
                            <button id="next-page" class="p-1.5 rounded bg-white shadow-sm hover:bg-slate-50 disabled:opacity-50 viewer-control" disabled>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-1 bg-slate-200/60 p-1 rounded-lg">
                            <button id="zoom-out" class="p-1.5 rounded bg-white shadow-sm hover:bg-slate-50 viewer-control" disabled>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                            </button>
                            <span id="zoom-percent" class="text-xs font-bold text-slate-600 px-2">100%</span>
                            <button id="zoom-in" class="p-1.5 rounded bg-white shadow-sm hover:bg-slate-50 viewer-control" disabled>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Document Container --}}
                <div class="relative bg-slate-800 p-4 min-h-[600px] flex justify-center items-start overflow-auto select-none" 
                     id="viewer-wrapper">

                    {{-- PDF Page Wrapper --}}
                    <div class="pdf-page-wrapper">
                        <canvas id="pdf-canvas"></canvas>
                    </div>

                    {{-- Loading Spinner --}}
                    <div id="loading-spinner" class="absolute inset-0 flex items-center justify-center bg-slate-800/80 z-20">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-10 h-10 border-4 border-slate-300 border-t-indigo-500 rounded-full animate-spin"></div>
                            <p class="text-slate-300 text-sm font-medium">Loading document securely...</p>
                        </div>
                    </div>
                </div>

            </x-ui.card>
        </x-protected-content-guard>
    </div>

    {{-- PDF.js Library via CDN --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const url = "{{ route('portal.learner.secure_doc.stream', $lesson->id) }}";
        let pdfDoc = null;
        let pageNum = 1;
        let pageRendering = false;
        let pageNumPending = null;
        let scale = 1.2;
        let isDocumentLoaded = false;
        const canvas = document.getElementById('pdf-canvas');
        const ctx = canvas.getContext('2d');

        function disableViewerControls() {
            document.querySelectorAll('.viewer-control').forEach(btn => {
                btn.disabled = true;
                btn.classList.add('disabled');
            });
        }

        function enableViewerControls() {
            document.querySelectorAll('.viewer-control').forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('disabled');
            });
        }

        function showViewerError(msg) {
            document.getElementById('loading-spinner').innerHTML = `
                <div class="text-center text-red-400 p-4">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-sm font-bold">${msg}</p>
                </div>
            `;
        }

        function renderPage(num) {
            pageRendering = true;
            document.getElementById('loading-spinner').style.display = 'flex';

            // Get page
            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({ scale: scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                // Render PDF page into canvas context
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                const renderTask = page.render(renderContext);

                renderTask.promise.then(function() {
                    pageRendering = false;
                    document.getElementById('loading-spinner').style.display = 'none';

                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });

            document.getElementById('page-num').textContent = num;
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function onPrevPage() {
            if (!pdfDoc || !isDocumentLoaded) {
                console.warn('PDF is not loaded yet.');
                return;
            }

            if (pageNum <= 1) {
                return;
            }

            pageNum--;
            queueRenderPage(pageNum);
        }
        document.getElementById('prev-page').addEventListener('click', onPrevPage);

        function onNextPage() {
            if (!pdfDoc || !isDocumentLoaded) {
                console.warn('PDF is not loaded yet.');
                return;
            }

            if (pageNum >= pdfDoc.numPages) {
                return;
            }

            pageNum++;
            queueRenderPage(pageNum);
        }
        document.getElementById('next-page').addEventListener('click', onNextPage);

        function zoomIn() {
            if (!pdfDoc || !isDocumentLoaded) return;
            scale += 0.1;
            document.getElementById('zoom-percent').textContent = Math.round(scale * 100) + '%';
            queueRenderPage(pageNum);
        }

        function zoomOut() {
            if (!pdfDoc || !isDocumentLoaded) return;
            if (scale <= 0.5) return;
            scale -= 0.1;
            document.getElementById('zoom-percent').textContent = Math.round(scale * 100) + '%';
            queueRenderPage(pageNum);
        }

        document.getElementById('zoom-in').addEventListener('click', zoomIn);
        document.getElementById('zoom-out').addEventListener('click', zoomOut);

        if (window.__PDF_VIEWER_STARTED__) {
            throw new Error('Duplicate PDF viewer script detected');
        }
        window.__PDF_VIEWER_STARTED__ = true;

        if (window.__securePdfLoading) {
            console.warn('PDF already loading, skipping duplicate init');
        } else {
            window.__securePdfLoading = true;
            loadSecurePdf();
        }

        async function loadSecurePdf() {
            const pdfStreamUrl = url + (url.includes('?') ? '&' : '?') + "t=" + Date.now();

            try {
                const res = await fetch(pdfStreamUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/pdf',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) {
                    throw new Error('PDF stream fetch failed: ' + res.status);
                }

                const contentType = (res.headers.get('content-type') || '').toLowerCase();
                if (!contentType.includes('application/pdf')) {
                    throw new Error('Secure document endpoint did not return a PDF');
                }

                const bytes = new Uint8Array(await res.arrayBuffer());

                const loadingTask = pdfjsLib.getDocument({
                    data: bytes,
                    disableStream: true,
                    disableRange: true,
                    disableAutoFetch: true
                });

                pdfDoc = await loadingTask.promise;
                isDocumentLoaded = true;
                pageNum = 1;

                document.getElementById('page-count').textContent = pdfDoc.numPages;

                enableViewerControls();

                renderPage(pageNum);

            } catch (error) {
                console.error('PDF load failed:', error);
                pdfDoc = null;
                isDocumentLoaded = false;
                disableViewerControls();
                showViewerError('Failed to load document securely. Please contact support.');
            }
        }

        // Security restrictions
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        window.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                alert('Printing is disabled for this secure document.');
            }
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                alert('Saving is disabled for this secure document.');
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'u') e.preventDefault();
            if (e.key === 'F12') e.preventDefault();
        });

    </script>
</x-app-layout>
