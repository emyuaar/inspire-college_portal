
<!-- Add Course Modal (Intelligent Redesign) -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content h-[85vh]">
      <div class="modal-header border-bottom-0 pb-0">
        <div>
            <h5 class="modal-title font-black text-2xl text-slate-900 tracking-tight" id="addCourseModalLabel">Add Courses for <span class="text-indigo-600">{{ $learner->first_name }}</span></h5>
            <p class="text-sm text-slate-500 font-medium">Configure course allocation and view partner-specific pricing.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body bg-slate-50 p-0 overflow-hidden d-flex flex-column mt-4">
        
        <div class="row g-0 h-100">
            {{-- LEFT PANEL: Available Courses --}}
            <div class="col-lg-7 col-md-6 border-end border-slate-200 d-flex flex-column h-100 bg-white">
                <div class="p-4 border-bottom border-slate-100 bg-white">
                    <div class="relative group">
                        <input type="text" id="courseSearchInput" 
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border-0 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-indigo-500/20 transition-all" 
                            placeholder="Search by title, level, or awarding body...">
                        <i class="fa-solid fa-search absolute left-4 top-3.5 text-slate-400 group-focus-within:text-indigo-600 transition-colors"></i>
                    </div>
                </div>
                
                <div id="availableCoursesList" class="flex-grow-1 overflow-auto p-4 space-y-4 custom-scrollbar">
                    {{-- JS renders items here --}}
                </div>
            </div>

            {{-- RIGHT PANEL: Selected Courses --}}
            <div class="col-lg-5 col-md-6 d-flex flex-column h-100 bg-slate-50/50">
                <div class="p-4 border-bottom border-slate-200 bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-black text-slate-900 uppercase tracking-widest text-[11px]">Selected Course</h6>
                    </div>
                    <button type="button" id="clearAllBtn" class="text-rose-600 text-[10px] font-black uppercase tracking-widest hover:text-rose-800 transition-colors">Clear All</button>
                </div>

                <div id="selectedCoursesList" class="flex-grow-1 overflow-auto p-4 space-y-3 custom-scrollbar">
                    {{-- JS renders selected items here --}}
                    <div id="emptyState" class="text-center py-12">
                        <div class="w-16 h-16 bg-white rounded-3xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-4 text-slate-200">
                            <i class="fa-solid fa-cart-plus text-2xl"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No courses selected</p>
                    </div>
                </div>

                {{-- Summary Section --}}
                <div class="p-6 bg-white border-top border-slate-200 space-y-3">
                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-500 uppercase tracking-widest">Combined Base Total</span>
                        <span class="text-slate-900" id="baseTotalDisplay">£0.00</span>
                    </div>
                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-500 uppercase tracking-widest">Estimated Partner Total</span>
                        <span class="text-indigo-600 text-lg font-black" id="totalPriceDisplay">£0.00</span>
                    </div>
                    <div id="globalDiscountNote" class="text-[10px] text-emerald-600 font-black uppercase tracking-widest text-right"></div>
                </div>
            </div>
        </div>

      </div>
      
      <div class="modal-footer bg-white border-top border-slate-100 p-4">
        <button type="button" class="btn btn-link text-slate-400 text-decoration-none font-bold text-sm" data-bs-dismiss="modal">Cancel</button>
        
        <form action="{{ route('partner.courses.store', $learner->id) }}" method="POST" id="addCoursesForm">
            @csrf
            <div id="hiddenInputsContainer"></div>
            <button type="submit" class="bg-slate-900 hover:bg-indigo-600 text-white font-black py-3 px-10 rounded-xl shadow-lg transition-all hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed" id="submitBtn" disabled>
                Next: Select Payment Plan
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
    window.coursesData = @json($courses);
    console.log("Courses Data:", window.coursesData);
    if (window.coursesData.length === 0) {
        console.warn("No courses found for this partner/learner.");
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableListEl = document.getElementById('availableCoursesList');
    const selectedListEl = document.getElementById('selectedCoursesList');
    const emptyStateEl = document.getElementById('emptyState');
    const searchInput = document.getElementById('courseSearchInput');
    const totalDisplay = document.getElementById('totalPriceDisplay');
    const baseTotalDisplay = document.getElementById('baseTotalDisplay');
    const globalDiscountNote = document.getElementById('globalDiscountNote');
    const hiddenContainer = document.getElementById('hiddenInputsContainer');
    const submitBtn = document.getElementById('submitBtn');
    const clearAllBtn = document.getElementById('clearAllBtn');
    const form = document.getElementById('addCoursesForm');

    let allCourses = window.coursesData || [];
    let selectedIds = new Set();

    console.log("Initializing modal with", allCourses.length, "courses");

    // Helper functions
    function fmt(val) {
        return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(val || 0);
    }
    function safeParse(val) {
        let p = parseFloat(val);
        return isNaN(p) ? 0 : p;
    }

    renderAvailable('');
    updateSelectedView();

    searchInput.addEventListener('input', (e) => {
        renderAvailable(e.target.value);
    });

    clearAllBtn.addEventListener('click', () => {
        selectedIds.clear();
        updateSelectedView();
        renderAvailable(searchInput.value);
    });

    function renderAvailable(filterText) {
        console.log("Rendering available courses with filter:", filterText);
        availableListEl.innerHTML = '';
        const term = filterText.toLowerCase();

        if (allCourses.length === 0) {
            availableListEl.innerHTML = '<div class="text-center py-12 text-slate-400">No courses available.</div>';
            return;
        }

        allCourses.forEach(course => {
            if (term && !course.title.toLowerCase().includes(term) && !String(course.awarding_body).toLowerCase().includes(term)) return;

            const isSelected = selectedIds.has(course.course_id);
            const card = document.createElement('div');
            card.className = `p-5 rounded-3xl border-2 transition-all duration-200 cursor-pointer ${isSelected ? 'bg-indigo-50/30 border-indigo-200 ring-2 ring-indigo-500/20' : 'bg-white border-slate-50 hover:border-indigo-100 shadow-sm hover:shadow-md'}`;
            card.onclick = () => addCourse(course.course_id);
            
            let modesHtml = '';
            if (course.allow_full) modesHtml += '<span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[9px] font-black uppercase rounded-md mr-1">Full Pay</span>';
            if (course.allow_three) modesHtml += '<span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-md mr-1">3 Months</span>';
            if (course.allow_inst) {
                if (course.has_plan) {
                    modesHtml += '<span class="px-2 py-0.5 bg-purple-50 text-purple-600 text-[9px] font-black uppercase rounded-md mr-1">Installments ✔</span>';
                } else {
                    modesHtml += '<span class="px-2 py-0.5 bg-amber-50 text-amber-600 text-[9px] font-black uppercase rounded-md mr-1">Installments ⚠</span>';
                }
            }

            const baseP = safeParse(course.base_price);
            const finalP = safeParse(course.final_full_price);
            const discLabel = course.discount_label ? `(-${course.discount_label})` : '';
            const awarding = course.awarding_body || 'N/A';
            const level = course.level_name || 'N/A';

            card.innerHTML = `
                <div class="flex items-start gap-4">
                    <div class="shrink-0 pt-1">
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all ${isSelected ? 'border-indigo-600 bg-indigo-600' : 'border-slate-200 bg-white'}">
                            ${isSelected ? '<i class="fas fa-check text-white text-[10px]"></i>' : ''}
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">${awarding} • ${level}</span>
                        </div>
                        <h4 class="font-black text-slate-900 leading-tight mb-3">${course.title}</h4>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Base Price</p>
                                <p class="text-sm font-bold text-slate-500">${course.price_status ? `<span class="text-rose-500">${course.price_status}</span>` : fmt(baseP)}</p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">Partner Price</p>
                                <p class="text-sm font-black text-indigo-600">${course.price_status ? 'N/A' : fmt(finalP)} <span class="text-[10px] text-emerald-500">${course.price_status ? '' : discLabel}</span></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-1">
                            ${modesHtml}
                        </div>
                    </div>
                </div>
            `;
            availableListEl.appendChild(card);
        });
    }

    window.addCourse = function(id) {
        selectedIds.clear(); 
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
        selectedListEl.querySelectorAll('.selected-row').forEach(el => el.remove());
        hiddenContainer.innerHTML = '';
        
        if (selectedIds.size === 0) {
            emptyStateEl.style.display = 'block';
            totalDisplay.innerText = '£0.00';
            baseTotalDisplay.innerText = '£0.00';
            globalDiscountNote.innerText = '';
            submitBtn.disabled = true;
            return;
        }

        emptyStateEl.style.display = 'none';
        let total = 0;
        let baseTotal = 0;

        selectedIds.forEach(id => {
            const course = allCourses.find(c => c.course_id === id);
            if (!course) return;

            const bPrice = safeParse(course.base_price);
            const fPrice = safeParse(course.final_full_price);

            total += fPrice;
            baseTotal += bPrice;

            const row = document.createElement('div');
            row.className = 'selected-row p-4 bg-white rounded-2xl border border-slate-100 shadow-sm flex justify-between items-start group animate-in fade-in slide-in-from-right-2';
            
            let statusHtml = '';
            if (course.allow_inst && !course.has_plan) {
                statusHtml = '<p class="text-[9px] font-black text-amber-600 uppercase mt-1">⚠ Installments Not Configured</p>';
            }

            row.innerHTML = `
                <div class="flex-1">
                    <h5 class="font-bold text-slate-900 text-sm leading-tight">${course.title}</h5>
                    <div class="flex gap-3 mt-1">
                        <span class="text-[10px] font-medium text-slate-400">Base: ${fmt(bPrice)}</span>
                        <span class="text-[10px] font-black text-indigo-600">Final: ${fmt(fPrice)}</span>
                    </div>
                    ${statusHtml}
                </div>
                <button type="button" class="w-6 h-6 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-rose-50 hover:text-rose-500 transition-colors" onclick="removeCourse(${id})">
                    <i class="fa-solid fa-times text-xs"></i>
                </button>
            `;
            selectedListEl.appendChild(row);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'course_ids[]';
            input.value = id;
            hiddenContainer.appendChild(input);
        });

        totalDisplay.innerText = fmt(total);
        baseTotalDisplay.innerText = fmt(baseTotal);
        
        const diff = baseTotal - total;
        if (diff > 0) {
            globalDiscountNote.innerText = `Total Discount Applied: ${fmt(diff)}`;
        } else {
            globalDiscountNote.innerText = '';
        }
        
        submitBtn.disabled = false;
    }
});
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    .modal-content.h-\[85vh\] { height: 85vh; border-radius: 2rem; overflow: hidden; }
</style>
