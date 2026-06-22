/**
 * Yuvalay MakerSpace Premium Visual CMS Inline Script
 */
document.addEventListener("DOMContentLoaded", () => {
    if (document.body.classList.contains("edit-mode-on")) {
        initInlineCms();
        initVisualListCms();
    }
});

/**
 * 1. SIMPLE TEXT INLINE EDITING (Double Click)
 */
function initInlineCms() {
    const editables = document.querySelectorAll("[data-cms-key]");
    editables.forEach(elem => {
        elem.classList.add("cms-editable");
        
        const indicator = document.createElement("span");
        indicator.className = "cms-edit-btn";
        indicator.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>';
        elem.appendChild(indicator);

        elem.addEventListener("dblclick", (e) => {
            e.stopPropagation();
            e.preventDefault();
            
            if (elem.querySelector(".cms-inline-editor")) return;

            const key = elem.getAttribute("data-cms-key");
            indicator.style.display = "none";
            const originalText = elem.innerText.trim();
            indicator.style.display = "";

            const isMultiline = originalText.length > 60 || elem.tagName === 'P';

            const editorDiv = document.createElement("div");
            editorDiv.className = "cms-inline-editor absolute z-[9999] bg-[#FFFFFF] border border-[#8DC63F] p-3 rounded-xl shadow-2xl flex flex-col gap-2 min-w-[280px]";
            editorDiv.style.top = "0px";
            editorDiv.style.left = "0px";

            let inputField;
            if (isMultiline) {
                inputField = document.createElement("textarea");
                inputField.rows = 4;
            } else {
                inputField = document.createElement("input");
                inputField.type = "text";
            }
            
            inputField.value = originalText;
            inputField.className = "bg-[#F3F4F6] border border-gray-300 text-black rounded-lg p-2 text-sm focus:outline-none focus:border-[#8DC63F] w-full";
            editorDiv.appendChild(inputField);

            const actionsDiv = document.createElement("div");
            actionsDiv.className = "flex justify-end gap-2 text-xs font-semibold";
            
            const cancelBtn = document.createElement("button");
            cancelBtn.innerText = "Cancel";
            cancelBtn.className = "px-2.5 py-1 rounded bg-gray-200 hover:bg-gray-300 text-black";
            cancelBtn.onclick = (event) => {
                event.stopPropagation();
                editorDiv.remove();
            };

            const saveBtn = document.createElement("button");
            saveBtn.innerText = "Save";
            saveBtn.className = "px-2.5 py-1 rounded bg-[#8DC63F] text-white hover:bg-[#6DA52A]";
            saveBtn.onclick = (event) => {
                event.stopPropagation();
                saveCmsText(key, inputField.value.trim(), elem, editorDiv);
            };

            actionsDiv.appendChild(cancelBtn);
            actionsDiv.appendChild(saveBtn);
            editorDiv.appendChild(actionsDiv);
            
            if (window.getComputedStyle(elem).position === 'static') {
                elem.style.position = 'relative';
            }
            elem.appendChild(editorDiv);
            inputField.focus();
        });
    });
}

function saveCmsText(key, value, element, editorDiv) {
    const formData = new FormData();
    formData.append("key", key);
    formData.append("value", value);

    fetch("/api.php?action=update-cms-text", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            element.innerHTML = "";
            element.innerText = value;
            
            const indicator = document.createElement("span");
            indicator.className = "cms-edit-btn";
            indicator.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>';
            element.appendChild(indicator);
            
            editorDiv.remove();
            showFloatingToast("Changes saved instantly!");
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Failed to save changes.");
    });
}

/**
 * 2. VISUAL CARD & LIST MANAGEMENT (Add, Delete, Reorder, Edit Modal)
 */
function initVisualListCms() {
    const lists = document.querySelectorAll("[data-cms-list]");
    
    lists.forEach(listContainer => {
        const tableName = listContainer.getAttribute("data-cms-list");
        
        // Add "Add Item" button at the bottom of each list container
        const addBtn = document.createElement("button");
        addBtn.className = "w-full border-2 border-dashed border-[#8DC63F] text-[#8DC63F] hover:bg-[#8DC63F]/5 font-bold py-3 rounded-2xl text-xs mt-6 flex items-center justify-center gap-1.5";
        addBtn.innerHTML = `<i class="fa-solid fa-plus-circle"></i> Add New Item to ${tableName.replace("_", " ")}`;
        addBtn.onclick = (e) => {
            e.preventDefault();
            openCmsListModal(tableName, 0, listContainer);
        };
        listContainer.appendChild(addBtn);

        // Scan children that have item IDs
        const items = listContainer.querySelectorAll("[data-cms-item-id]");
        items.forEach(item => {
            item.style.position = "relative";
            item.classList.add("group/cms-item");

            // Create controls overlay toolbar
            const toolbar = document.createElement("div");
            toolbar.className = "absolute top-2 right-2 z-40 bg-white/95 border border-gray-200 shadow-xl rounded-xl p-1 flex gap-1 items-center opacity-0 group-hover/cms-item:opacity-100 transition-opacity duration-300";
            
            const id = item.getAttribute("data-cms-item-id");

            toolbar.innerHTML = `
                <button onclick="moveCmsListItem('${tableName}', ${id}, 'up', this)" class="p-1.5 hover:bg-gray-100 rounded text-gray-700 text-xs" title="Move Up"><i class="fa-solid fa-arrow-up"></i></button>
                <button onclick="moveCmsListItem('${tableName}', ${id}, 'down', this)" class="p-1.5 hover:bg-gray-100 rounded text-gray-700 text-xs" title="Move Down"><i class="fa-solid fa-arrow-down"></i></button>
                <button onclick="openCmsListModal('${tableName}', ${id}, null)" class="p-1.5 hover:bg-gray-100 rounded text-brandGreen text-xs" title="Edit Card"><i class="fa-solid fa-pencil"></i></button>
                <button onclick="duplicateCmsListItem('${tableName}', ${id})" class="p-1.5 hover:bg-gray-100 rounded text-blue-500 text-xs" title="Duplicate"><i class="fa-solid fa-clone"></i></button>
                <button onclick="deleteCmsListItem('${tableName}', ${id}, this)" class="p-1.5 hover:bg-red-50 text-red-500 rounded text-xs" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
            `;
            item.appendChild(toolbar);
        });
    });
}

/**
 * 3. DIALOG MODAL FOR CARD CREATION & EDITING
 */
function openCmsListModal(table, id, listContainer = null) {
    // Schema fields definition for modals
    const schemas = {
        'milestones': [
            { name: 'year', label: 'Year/Date', type: 'text' },
            { name: 'title', label: 'Title', type: 'text' },
            { name: 'description', label: 'Milestone Details', type: 'textarea' },
            { name: 'display_order', label: 'Display Order', type: 'number', default: '0' }
        ],
        'team_members': [
            { name: 'name', label: 'Full Name', type: 'text' },
            { name: 'role', label: 'Role / Designation', type: 'text' },
            { name: 'image_url', label: 'Photo Image URL', type: 'text' },
            { name: 'description', label: 'Profile Summary', type: 'textarea' },
            { name: 'type', label: 'Role Categorization', type: 'select', options: ['team', 'mentor', 'volunteer'] },
            { name: 'display_order', label: 'Display Order', type: 'number', default: '0' }
        ],
        'workspaces': [
            { name: 'title', label: 'Workspace Title', type: 'text' },
            { name: 'description', label: 'Workspace Description', type: 'textarea' },
            { name: 'icon', label: 'FontAwesome Icon Class', type: 'text', placeholder: 'e.g. fa-solid fa-print' },
            { name: 'image_url', label: 'Image URL', type: 'text' },
            { name: 'features_json', label: 'Features Specs (JSON Array)', type: 'textarea', placeholder: '["Feature 1", "Feature 2"]' },
            { name: 'display_order', label: 'Display Order', type: 'number', default: '0' }
        ],
        'certifications': [
            { name: 'code', label: 'Accreditation Code', type: 'text', placeholder: 'e.g. C1' },
            { name: 'title', label: 'Certificate Name', type: 'text' },
            { name: 'description', label: 'Details', type: 'textarea' },
            { name: 'display_order', label: 'Display Order', type: 'number', default: '0' }
        ],
        'testimonials': [
            { name: 'name', label: 'Author Name', type: 'text' },
            { name: 'role', label: 'Author Subtitle / College', type: 'text' },
            { name: 'text', label: 'Quote Description', type: 'textarea' },
            { name: 'image_url', label: 'Avatar Photo URL', type: 'text' },
            { name: 'rating', label: 'Rating (1-5)', type: 'number', default: '5' }
        ],
        'gallery': [
            { name: 'media_url', label: 'Photo/Media URL', type: 'text' },
            { name: 'media_type', label: 'Media Type', type: 'select', options: ['image', 'video'] },
            { name: 'caption', label: 'Caption / Details', type: 'text' },
            { name: 'category', label: 'Category Tag', type: 'text', default: 'General' }
        ],
        'navigation_items': [
            { name: 'label', label: 'Link Title', type: 'text' },
            { name: 'link', label: 'Link Target URL', type: 'text' },
            { name: 'display_order', label: 'Display Order', type: 'number', default: '0' }
        ]
    };

    const schema = schemas[table];
    if (!schema) {
        alert("CMS Schema not configured for: " + table);
        return;
    }

    // Create modal element
    const modalDiv = document.createElement("div");
    modalDiv.id = "cms_card_editor_modal";
    modalDiv.className = "fixed inset-0 bg-black/80 backdrop-blur-sm z-[99999] flex items-center justify-center p-4";
    
    let fieldsHtml = "";
    schema.forEach(field => {
        let inputHtml = "";
        const ph = field.placeholder ? `placeholder="${field.placeholder}"` : '';
        const def = field.default ? `value="${field.default}"` : '';

        if (field.type === 'textarea') {
            inputHtml = `<textarea name="${field.name}" rows="3" ${ph} class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-brandGreen" id="modal_field_${field.name}"></textarea>`;
        } else if (field.type === 'select') {
            let opts = "";
            field.options.forEach(opt => {
                opts += `<option value="${opt}">${opt.toUpperCase()}</option>`;
            });
            inputHtml = `<select name="${field.name}" class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-brandGreen" id="modal_field_${field.name}">${opts}</select>`;
        } else if (field.name === 'image_url' || field.name === 'media_url' || field.name === 'avatar' || field.name === 'photo') {
            // Image field — show file picker + URL input + preview
            const previewId = `modal_preview_${field.name}`;
            const uploadFnCall = `if(typeof uploadImageFile==='function'){uploadImageFile(this,'modal_field_${field.name}','${previewId}')}else{var fd=new FormData();fd.append('file',this.files[0]);fetch('/api.php?action=media-upload',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success'){document.getElementById('modal_field_${field.name}').value=d.data.file_url;var p=document.getElementById('${previewId}');if(p){p.src=d.data.file_url;p.style.display='';}}else{alert(d.message);}}).catch(e=>alert(e));}`;
            inputHtml = `
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <label class="flex items-center gap-2 cursor-pointer px-3 py-2 bg-[#8DC63F] hover:bg-[#6DA52A] text-black font-bold text-xs rounded-xl transition-all">
                    <i class="fa-solid fa-folder-open"></i> Choose File
                    <input type="file" accept="image/*" class="hidden" onchange="${uploadFnCall}">
                  </label>
                  <span class="text-gray-400 text-xs">or paste URL</span>
                </div>
                <input type="text" name="${field.name}" ${def} ${ph} class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-brandGreen" id="modal_field_${field.name}" oninput="var p=document.getElementById('${previewId}');if(p&&this.value){p.src=this.value;p.style.display='';}">
                <img id="${previewId}" class="mt-2 h-20 w-full object-cover rounded-xl border border-gray-200" style="display:none" onerror="this.style.display='none'">
              </div>
            `;
        } else {
            inputHtml = `<input type="${field.type}" name="${field.name}" ${def} ${ph} class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-brandGreen" id="modal_field_${field.name}">`;
        }

        fieldsHtml += `
            <div>
                <label class="block text-gray-700 font-semibold mb-1 text-xs text-left">${field.label}</label>
                ${inputHtml}
            </div>
        `;
    });

    modalDiv.innerHTML = `
        <div class="bg-white w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <h3 class="font-extrabold text-base text-gray-800 uppercase tracking-wider">${id > 0 ? 'Edit' : 'Add New'} ${table.replace("_", " ")}</h3>
                <button onclick="document.getElementById('cms_card_editor_modal').remove()" class="text-gray-400 hover:text-black"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form id="cms_card_form" class="p-6 space-y-4 overflow-y-auto flex-grow">
                ${fieldsHtml}
            </form>
            <div class="p-6 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                <button onclick="document.getElementById('cms_card_editor_modal').remove()" class="px-4 py-2.5 rounded-xl bg-gray-200 hover:bg-gray-300 font-bold text-xs text-black">Cancel</button>
                <button id="cms_card_save_btn" class="px-5 py-2.5 rounded-xl bg-brandGreen hover:bg-brandDarkGreen font-bold text-xs text-white shadow-lg shadow-brandGreen/25">Save Item</button>
            </div>
        </div>
    `;

    document.body.appendChild(modalDiv);

    // If ID is set (>0), pull existing card data to populate fields
    if (id > 0) {
        // Find existing elements or fields from domestic HTML nodes
        // Or simply we can look up from the domestic page or fallback to blank. Let's pull from DOM if possible or query it
        const currentItem = document.querySelector(`[data-cms-item-id="${id}"]`);
        if (currentItem) {
            schema.forEach(field => {
                // look for matching spans/sub-elements
                const el = currentItem.querySelector(`.cms-field-${field.name}`);
                const input = document.getElementById(`modal_field_${field.name}`);
                if (el && input) {
                    input.value = el.innerText.trim();
                }
            });
        }
    }

    // Save button event handler
    document.getElementById("cms_card_save_btn").onclick = (e) => {
        e.preventDefault();
        const form = document.getElementById("cms_card_form");
        const formData = new FormData(form);
        
        const outputFields = {};
        schema.forEach(field => {
            outputFields[field.name] = document.getElementById(`modal_field_${field.name}`).value.trim();
        });

        const postData = new FormData();
        postData.append("table", table);
        postData.append("id", id);
        for (const [k, v] of Object.entries(outputFields)) {
            postData.append(`fields[${k}]`, v);
        }

        fetch("/api.php?action=update-cms-list", {
            method: "POST",
            body: postData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showFloatingToast("Card saved successfully!");
                document.getElementById('cms_card_editor_modal').remove();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert("Error: " + data.message);
            }
        });
    };
}

/**
 * 4. REORDERING ITEMS VIA AJAX
 */
window.moveCmsListItem = function(table, id, direction, btn) {
    const item = btn.closest("[data-cms-item-id]");
    const sibling = direction === 'up' ? item.previousElementSibling : item.nextElementSibling;
    
    if (!sibling || !sibling.hasAttribute("data-cms-item-id")) {
        showFloatingToast("No item in that direction to swap with!");
        return;
    }

    // Swap displays visually instantly
    if (direction === 'up') {
        item.parentNode.insertBefore(item, sibling);
    } else {
        item.parentNode.insertBefore(sibling, item);
    }

    // Recalculate display orders for all items in this parent wrapper
    const parent = item.parentNode;
    const allItems = parent.querySelectorAll("[data-cms-item-id]");
    const promises = [];
    
    allItems.forEach((itm, index) => {
        const itmId = itm.getAttribute("data-cms-item-id");
        const postData = new FormData();
        postData.append("table", table);
        postData.append("id", itmId);
        postData.append("fields[display_order]", index + 1);

        promises.push(
            fetch("/api.php?action=update-cms-list", {
                method: "POST",
                body: postData
            })
        );
    });

    Promise.all(promises)
        .then(() => showFloatingToast("Order updated and saved!"))
        .catch(err => console.error("Ordering save error: ", err));
};

/**
 * 5. DUPLICATE LIST ITEM
 */
window.duplicateCmsListItem = function(table, id) {
    if (!confirm("Are you sure you want to duplicate this item?")) return;

    // Fetch values from DOM element
    const currentItem = document.querySelector(`[data-cms-item-id="${id}"]`);
    if (!currentItem) return;

    // Gather inputs dynamically
    const fields = {};
    const fieldSpans = currentItem.querySelectorAll("[class*='cms-field-']");
    fieldSpans.forEach(span => {
        const match = span.className.match(/cms-field-([a-zA-Z0-9_-]+)/);
        if (match) {
            fields[match[1]] = span.innerText.trim();
        }
    });

    // Make copy additions
    if (fields['title']) fields['title'] = fields['title'] + " (Copy)";
    if (fields['name']) fields['name'] = fields['name'] + " (Copy)";
    fields['display_order'] = 99; // append to end

    const postData = new FormData();
    postData.append("table", table);
    postData.append("id", 0); // insert
    for (const [k, v] of Object.entries(fields)) {
        postData.append(`fields[${k}]`, v);
    }

    fetch("/api.php?action=update-cms-list", {
        method: "POST",
        body: postData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showFloatingToast("Duplicated successfully!");
            setTimeout(() => window.location.reload(), 800);
        } else {
            alert(data.message);
        }
    });
};

/**
 * 6. DELETE LIST ITEM
 */
window.deleteCmsListItem = function(table, id, btn) {
    if (!confirm("Are you sure you want to delete this content item? This action is permanent.")) return;

    const postData = new FormData();
    postData.append("table", table);
    postData.append("id", id);

    fetch("/api.php?action=delete-cms-item", {
        method: "POST",
        body: postData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showFloatingToast("Item deleted successfully!");
            btn.closest("[data-cms-item-id]").remove();
        } else {
            alert(data.message);
        }
    });
};

/**
 * 7. GLOBAL EDIT PANEL MODALS (PRE-EXISTING OVERRIDES INTEGRATION)
 */
window.openGlobalCmsModal = function() {
    const modal = document.getElementById("cmsGlobalModal");
    const content = document.getElementById("cmsModalContent");
    content.innerHTML = '<div class="text-center py-6 text-gray-400"><i class="fa-solid fa-spinner animate-spin text-xl mr-2"></i> Loading settings...</div>';
    modal.classList.remove("hidden");

    content.innerHTML = `
        <div class="space-y-4 text-sm">
            <div>
                <label class="block text-gray-500 font-medium mb-1 text-left text-xs">Website Title Name</label>
                <input type="text" id="cfg_site_name" class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-[#8DC63F]">
            </div>
            <div>
                <label class="block text-gray-500 font-medium mb-1 text-left text-xs">MakerSpace Phone Number</label>
                <input type="text" id="cfg_phone" class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-[#8DC63F]">
            </div>
            <div>
                <label class="block text-gray-500 font-medium mb-1 text-left text-xs">MakerSpace Email Address</label>
                <input type="email" id="cfg_email" class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-[#8DC63F]">
            </div>
            <div>
                <label class="block text-gray-500 font-medium mb-1 text-left text-xs">MakerSpace Physical Address</label>
                <textarea id="cfg_address" rows="2" class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-[#8DC63F]"></textarea>
            </div>
            <div>
                <label class="block text-gray-500 font-medium mb-1 text-left text-xs">Working Hours Description</label>
                <input type="text" id="cfg_hours" class="w-full bg-gray-100 border border-gray-300 rounded-xl p-3 text-black focus:outline-none focus:border-[#8DC63F]">
            </div>
        </div>
    `;
    
    // Fetch values from DOM elements
    const phoneEl = document.querySelector("[data-cms-key='contact_phone']");
    const emailEl = document.querySelector("[data-cms-key='contact_email']");
    const addrEl = document.querySelector("[data-cms-key='contact_address']");
    
    if (phoneEl) document.getElementById("cfg_phone").value = phoneEl.innerText.trim();
    if (emailEl) document.getElementById("cfg_email").value = emailEl.innerText.trim();
    if (addrEl) document.getElementById("cfg_address").value = addrEl.innerText.trim();
    
    document.getElementById("cfg_site_name").value = "Yuvalay MakerSpace";
    document.getElementById("cfg_hours").value = "Tuesday - Sunday: 10:00 AM - 08:00 PM (Monday Closed)";
};

window.saveGlobalCmsSettings = function() {
    const site = document.getElementById("cfg_site_name").value;
    const phone = document.getElementById("cfg_phone").value;
    const email = document.getElementById("cfg_email").value;
    const address = document.getElementById("cfg_address").value;
    const hours = document.getElementById("cfg_hours").value;

    const promises = [];
    
    const settings = {
        'design_site_name': site,
        'contact_phone': phone,
        'contact_email': email,
        'contact_address': address,
        'contact_hours': hours
    };

    for (const [key, val] of Object.entries(settings)) {
        const formData = new FormData();
        formData.append("key", key);
        formData.append("value", val);
        
        promises.push(
            fetch("/api.php?action=update-cms-text", {
                method: "POST",
                body: formData
            }).then(res => res.json())
        );
    }

    Promise.all(promises)
        .then(() => {
            closeCmsModal('cmsGlobalModal');
            showFloatingToast("Global configurations updated successfully!");
            setTimeout(() => window.location.reload(), 1000);
        })
        .catch(err => alert("Error updating settings: " + err));
};

window.openSlideshowCmsModal = function() {
    const modal = document.getElementById("cmsSlidesModal");
    const container = document.getElementById("cmsSlidesContent");
    container.innerHTML = '<div class="text-center py-6 text-gray-400"><i class="fa-solid fa-spinner animate-spin text-xl mr-2"></i> Loading slides...</div>';
    modal.classList.remove("hidden");

    fetch("/api.php?action=get-cms-slides")
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                renderSlidesList(data.slides);
            } else {
                container.innerHTML = `<div class="text-red-400">Failed to load slides: ${data.message}</div>`;
            }
        });
};

function renderSlidesList(slides) {
    const container = document.getElementById("cmsSlidesContent");
    if (slides.length === 0) {
        container.innerHTML = '<p class="text-gray-400 text-sm text-center py-4">No slideshow slides found. Add one below!</p>';
        return;
    }

    let html = '<div class="space-y-4">';
    slides.forEach(slide => {
        html += `
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3 relative text-left" id="slide_card_${slide.id}">
                <div class="flex items-center gap-3">
                    <img src="${slide.image_url}" id="slide_thumb_${slide.id}" class="w-16 h-10 object-cover rounded-lg bg-gray-900 border border-gray-300" onerror="this.src='https://placehold.co/150x100?text=No+Image'">
                    <div class="flex-grow">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase">Slide ID: ${slide.id}</span>
                        <input type="text" value="${slide.title}" id="slide_title_${slide.id}" class="bg-transparent text-gray-800 font-semibold text-sm border-b border-transparent focus:border-[#8DC63F] focus:outline-none w-full py-0.5" placeholder="Slide Title">
                    </div>
                    <button onclick="deleteCmsSlide(${slide.id})" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg text-sm transition-all" title="Delete Slide"><i class="fa-solid fa-trash-can"></i></button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="sm:col-span-2">
                        <label class="block text-gray-500 mb-0.5">Image URL</label>
                        <input type="text" value="${slide.image_url}" id="slide_img_${slide.id}" class="w-full bg-white border border-gray-300 rounded-lg px-2 py-1 text-gray-800 focus:outline-none focus:border-[#8DC63F]">
                        <!-- File upload button -->
                        <div class="mt-1.5 flex items-center gap-2">
                            <input type="file" id="slide_file_${slide.id}" accept="image/*" class="hidden" onchange="uploadSlideImage(${slide.id}, this)">
                            <button type="button" onclick="document.getElementById('slide_file_${slide.id}').click()"
                                class="flex items-center gap-1.5 px-2.5 py-1 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 hover:border-[#8DC63F] hover:text-[#6DA52A] transition-all text-[11px] font-semibold">
                                <i class="fa-solid fa-folder-open text-[10px]"></i> Choose File
                            </button>
                            <span id="slide_upload_status_${slide.id}" class="text-[10px] text-gray-400 italic"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-500 mb-0.5">Order</label>
                        <input type="number" value="${slide.display_order}" id="slide_order_${slide.id}" class="w-full bg-white border border-gray-300 rounded-lg px-2 py-1 text-gray-800 focus:outline-none focus:border-[#8DC63F]">
                    </div>
                </div>
                <div class="text-xs">
                    <label class="block text-gray-500 mb-0.5">Subtitle / Description</label>
                    <input type="text" value="${slide.subtitle}" id="slide_sub_${slide.id}" class="w-full bg-white border border-gray-300 rounded-lg px-2 py-1 text-gray-800 focus:outline-none focus:border-[#8DC63F]">
                </div>
                <div class="flex justify-end pt-1">
                    <button onclick="saveCmsSlide(${slide.id})" class="px-3 py-1.5 rounded-lg bg-[#8DC63F] text-white font-bold text-xs hover:bg-[#6DA52A] transition-colors"><i class="fa-solid fa-floppy-disk mr-1"></i> Save Slide</button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

/**
 * Upload a local image file for a specific slide card.
 * Updates the Image URL input and the thumbnail preview on success.
 */
window.uploadSlideImage = function(slideId, inputEl) {
    if (!inputEl.files || !inputEl.files[0]) return;

    const statusEl = document.getElementById(`slide_upload_status_${slideId}`);
    const imgUrlEl = document.getElementById(`slide_img_${slideId}`);
    const thumbEl  = document.getElementById(`slide_thumb_${slideId}`);

    statusEl.textContent = 'Uploading…';
    statusEl.classList.remove('text-red-500', 'text-green-600');
    statusEl.classList.add('text-gray-400');

    const formData = new FormData();
    formData.append('file', inputEl.files[0]);
    formData.append('folder', 'Slideshow');

    fetch('/api.php?action=media-upload', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const url = data.data && data.data.file_url ? data.data.file_url : (data.file_url || '');
            imgUrlEl.value = url;
            if (thumbEl) thumbEl.src = url;
            statusEl.textContent = '✓ Uploaded!';
            statusEl.classList.remove('text-gray-400', 'text-red-500');
            statusEl.classList.add('text-green-600');
            showFloatingToast('Image uploaded! Click Save Slide to apply.');
        } else {
            statusEl.textContent = 'Upload failed: ' + (data.message || 'Unknown error');
            statusEl.classList.remove('text-gray-400', 'text-green-600');
            statusEl.classList.add('text-red-500');
        }
        // Reset the file input so the same file can be re-selected
        inputEl.value = '';
    })
    .catch(err => {
        statusEl.textContent = 'Error: ' + err.message;
        statusEl.classList.remove('text-gray-400', 'text-green-600');
        statusEl.classList.add('text-red-500');
        inputEl.value = '';
    });
};

window.saveCmsSlide = function(id) {
    const title = document.getElementById(`slide_title_${id}`).value;
    const subtitle = document.getElementById(`slide_sub_${id}`).value;
    const img = document.getElementById(`slide_img_${id}`).value;
    const order = document.getElementById(`slide_order_${id}`).value;

    const formData = new FormData();
    formData.append("id", id);
    formData.append("title", title);
    formData.append("subtitle", subtitle);
    formData.append("image_url", img);
    formData.append("display_order", order);

    fetch("/api.php?action=update-cms-slide", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showFloatingToast("Slide details saved!");
            openSlideshowCmsModal();
        } else {
            alert(data.message);
        }
    });
};

window.addNewCmsSlide = function() {
    const formData = new FormData();
    formData.append("id", 0);
    formData.append("title", "New Slideshow Slide Title");
    formData.append("subtitle", "Subheading details for this slides content.");
    formData.append("image_url", "https://images.unsplash.com/photo-1581092921461-eab62e97a780?auto=format&fit=crop&w=1200&q=80");
    formData.append("display_order", 5);

    fetch("/api.php?action=update-cms-slide", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showFloatingToast("Added new slide!");
            openSlideshowCmsModal();
        } else {
            alert(data.message);
        }
    });
};

window.deleteCmsSlide = function(id) {
    if (!confirm("Are you sure you want to delete this slide?")) return;

    const formData = new FormData();
    formData.append("id", id);

    fetch("/api.php?action=delete-cms-slide", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showFloatingToast("Slide deleted!");
            openSlideshowCmsModal();
        } else {
            alert(data.message);
        }
    });
};

window.closeCmsModal = function(modalId) {
    document.getElementById(modalId).classList.add("hidden");
};

/**
 * 8. FLOATING ALERT TOAST
 */
function showFloatingToast(msg) {
    const toast = document.createElement("div");
    toast.className = "fixed top-6 right-6 z-[999999] bg-[#FFFFFF] border border-[#8DC63F] text-black px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-2 transform translate-y-2 opacity-0 transition-all duration-300 font-semibold text-xs";
    toast.innerHTML = `<i class="fa-solid fa-circle-check text-[#8DC63F] text-sm"></i> ${msg}`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateY(0px)";
    }, 50);

    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateY(-10px)";
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}
