
<!-- Add Course Modal (Dual Pane Redesign) -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content h-[85vh]"> {{-- Fixed height for modal content --}}
      <div class="modal-header">
        <div>
            <h5 class="modal-title font-bold text-lg" id="addCourseModalLabel">Add Courses for {{ $learner->first_name }}</h5>
            <p class="text-sm text-slate-500">Select courses from the left to add them to the enrolment list.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body bg-slate-50 p-0 overflow-hidden d-flex flex-column">
        
        <div class="row g-0 h-100">
            {{-- LEFT PANEL: Available Courses --}}
            <div class="col-lg-6 col-md-6 border-end border-slate-200 d-flex flex-column h-100 bg-white">
                <div class="p-3 border-bottom border-slate-100 bg-slate-50">
                    <h6 class="fw-bold mb-2 text-slate-700">Available Courses</h6>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></span>
                        <input type="text" id="courseSearchInput" class="form-control border-start-0" placeholder="Search by title...">
                    </div>
                </div>
                
                <div id="availableCoursesList" class="flex-grow-1 overflow-auto p-3 space-y-2 custom-scrollbar">
                    {{-- JS renders items here --}}
                </div>
            </div>

            {{-- RIGHT PANEL: Selected Courses --}}
            <div class="col-lg-6 col-md-6 d-flex flex-column h-100 bg-slate-50">
                <div class="p-3 border-bottom border-slate-200 bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-slate-700">Selected Courses <span id="selectedCountBadge" class="badge bg-indigo-600 rounded-pill ms-1">0</span></h6>
                    </div>
                    <button type="button" id="clearAllBtn" class="btn btn-link btn-sm text-danger text-decoration-none p-0" style="font-size: 0.8rem;">Clear All</button>
                </div>

                <div id="selectedCoursesList" class="flex-grow-1 overflow-auto p-3 space-y-2 custom-scrollbar">
                    {{-- JS renders selected items here --}}
                    <div id="emptyState" class="text-center py-5 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <p class="text-sm">No courses selected yet.</p>
                    </div>
                </div>

                <div class="p-3 bg-white border-top border-slate-200">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-sm font-semibold text-slate-600">Total Estimated:</span>
                        <span class="text-lg font-bold text-slate-900" id="totalPriceDisplay">£0.00</span>
                    </div>
                </div>
            </div>
        </div>

      </div>
      
      <div class="modal-footer bg-white border-top border-slate-100">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        
        <form action="{{ route('partner.courses.store', ($isPending ?? false) ? 'pending-' . $learner->id : $learner->id) }}" method="POST" id="addCoursesForm">
            @csrf
            {{-- Hidden inputs injected by JS --}}
            <div id="hiddenInputsContainer"></div>
            <button type="submit" class="btn btn-primary bg-slate-900 border-slate-900" id="submitBtn" disabled>
                Create Enrolments
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Data Payload --}}
<script>
    window.coursesData = @json($courses);
</script>

{{-- Logic --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableListEl = document.getElementById('availableCoursesList');
    const selectedListEl = document.getElementById('selectedCoursesList');
    const emptyStateEl = document.getElementById('emptyState');
    const searchInput = document.getElementById('courseSearchInput');
    const countBadge = document.getElementById('selectedCountBadge');
    const totalDisplay = document.getElementById('totalPriceDisplay');
    const hiddenContainer = document.getElementById('hiddenInputsContainer');
    const submitBtn = document.getElementById('submitBtn');
    const clearAllBtn = document.getElementById('clearAllBtn');
    const form = document.getElementById('addCoursesForm');

    let allCourses = window.coursesData || [];
    let selectedIds = new Set();

    // Initial Render
    renderAvailable('');
    updateSelectedView();

    // Search Event
    searchInput.addEventListener('input', (e) => {
        renderAvailable(e.target.value);
    });

    // Clear All
    clearAllBtn.addEventListener('click', () => {
        selectedIds.clear();
        updateSelectedView();
        renderAvailable(searchInput.value);
    });

    // Form Submit Intercept (Validation safety)
    form.addEventListener('submit', (e) => {
        if (selectedIds.size === 0) {
            e.preventDefault();
            alert('Please select at least one course.');
        }
    });

    function renderAvailable(filterText) {
        availableListEl.innerHTML = '';
        const term = filterText.toLowerCase();

        allCourses.forEach(course => {
            // Filter
            if (term && !course.title.toLowerCase().includes(term)) return;

            const isSelected = selectedIds.has(course.course_id);
            
            // Create Card
            const card = document.createElement('div');
            card.className = `p-3 rounded-lg border flex justify-between items-center transition-all ${isSelected ? 'bg-slate-50 border-slate-100 opacity-60' : 'bg-white border-slate-200 hover:border-indigo-300 shadow-sm'}`;
            
            let badges = '';
            if (course.is_promo) badges += `<span class="badge bg-red-100 text-red-800 me-1">Promo</span>`;
            if (course.installment_plan.available) badges += `<span class="badge bg-blue-50 text-blue-700">Installment</span>`;

            card.innerHTML = `
                <div>
                    <div class="fw-bold text-sm text-slate-800">${course.title}</div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-bold text-slate-700">£${course.final_full_price}</span>
                        ${badges}
                    </div>
                </div>
                <button type="button" class="btn btn-sm ${isSelected ? 'btn-secondary disabled' : 'btn-outline-primary'}" ${isSelected ? 'disabled' : ''} onclick="addCourse(${course.course_id})">
                    ${isSelected ? 'Added' : '+ Add'}
                </button>
            `;
            availableListEl.appendChild(card);
        });
    }

    window.addCourse = function(id) {
        selectedIds.add(id);
        renderAvailable(searchInput.value);
        updateSelectedView();
    };

    window.removeCourse = function(id) {
        selectedIds.delete(id);
        renderAvailable(searchInput.value);
        updateSelectedView();
    };

    function updateSelectedView() {
        selectedListEl.innerHTML = '';
        hiddenContainer.innerHTML = '';
        
        if (selectedIds.size === 0) {
            selectedListEl.appendChild(emptyStateEl);
            emptyStateEl.style.display = 'block';
            countBadge.innerText = '0';
            totalDisplay.innerText = '£0.00';
            submitBtn.disabled = true;
            return;
        }

        emptyStateEl.style.display = 'none';
        let total = 0;

        selectedIds.forEach(id => {
            const course = allCourses.find(c => c.course_id === id);
            if (!course) return;

            total += parseFloat(course.final_full_price);

            // UI Row
            const row = document.createElement('div');
            row.className = 'p-3 bg-white rounded-lg border border-slate-200 shadow-sm flex justify-between items-center mb-2 animate-in fade-in slide-in-from-left-2 duration-200';
            row.innerHTML = `
                <div>
                    <div class="fw-bold text-sm text-slate-900">${course.title}</div>
                    <div class="text-xs text-slate-500">£${course.final_full_price}</div>
                </div>
                <button type="button" class="btn-close btn-sm" aria-label="Remove" onclick="removeCourse(${id})"></button>
            `;
            selectedListEl.appendChild(row);

            // Hidden Input
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'course_ids[]';
            input.value = id;
            hiddenContainer.appendChild(input);
        });

        countBadge.innerText = selectedIds.size;
        totalDisplay.innerText = '£' + total.toFixed(2);
        submitBtn.disabled = false;
    }
});
</script>

<style>
    /* Scoped Styles for Modal */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9; 
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8; 
    }
    
    /* Ensure height responsiveness */
    .modal-content.h-\[85vh\] {
        height: 85vh;
    }
</style>
