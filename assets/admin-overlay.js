/**
 * XPressUI Bridge — Customize Workflow page scripts.
 */
/* global xpressuiBridgeAdmin */
let xpressuiProFormDirty = false;
const xpressuiProForm = document.querySelector('.xpressui-admin-wrap form');
const xpressuiDirtyStatus = document.querySelector('[data-xpressui-dirty-status]');
const xpressuiCardSearch = document.querySelector('[data-xpressui-card-search]');
const xpressuiVisibleCount = document.querySelector('[data-xpressui-visible-count]');
const xpressuiEmptyState = document.querySelector('[data-xpressui-empty-state]');
let xpressuiCustomizedOnly = false;
function xpressuiSetDirtyState(isDirty){
	xpressuiProFormDirty = isDirty;
	if(!xpressuiDirtyStatus){return;}
	xpressuiDirtyStatus.classList.toggle('is-dirty', isDirty);
	xpressuiDirtyStatus.classList.toggle('is-saved', !isDirty);
	xpressuiDirtyStatus.textContent = isDirty ? 'Unsaved changes' : 'No unsaved changes';
}
function xpressuiApplyCardFilters(){
	const container = document.querySelector('.xpressui-admin-wrap');
	if(!container){return;}
	const query = xpressuiCardSearch && typeof xpressuiCardSearch.value === 'string'
		? xpressuiCardSearch.value.trim().toLowerCase()
		: '';
	let visibleCount = 0;
	container.querySelectorAll('details.xpressui-admin-card').forEach(function(card){
		const searchText = (card.getAttribute('data-xpressui-search-text') || '').toLowerCase();
		const isCustomized = card.getAttribute('data-xpressui-customized') === '1';
		const matchesQuery = !query || searchText.indexOf(query) !== -1;
		const matchesCustomized = !xpressuiCustomizedOnly || isCustomized;
		const isVisible = matchesQuery && matchesCustomized;
		card.classList.toggle('is-filtered-out', !isVisible);
		if(isVisible){
			visibleCount += 1;
			if(query){
				card.open = true;
			}
		}
	});
	if(xpressuiVisibleCount){
		xpressuiVisibleCount.textContent = String(visibleCount);
	}
	if(xpressuiEmptyState){
		xpressuiEmptyState.style.display = visibleCount === 0 ? '' : 'none';
	}
}
if(xpressuiProForm){
	xpressuiProForm.addEventListener('input', function(){ xpressuiSetDirtyState(true); });
	xpressuiProForm.addEventListener('change', function(){ xpressuiSetDirtyState(true); });
	xpressuiProForm.addEventListener('submit', function(){ xpressuiSetDirtyState(false); });
	window.addEventListener('beforeunload', function(event){
		if(!xpressuiProFormDirty){return;}
		event.preventDefault();
		event.returnValue = '';
	});
}
if(xpressuiCardSearch){
	xpressuiCardSearch.addEventListener('input', function(){
		xpressuiApplyCardFilters();
	});
}
document.addEventListener('click', function(event){
	const trigger = event.target.closest('.xpressui-pro-details-toggle');
	if(!trigger){return;}
	const container = document.querySelector('.xpressui-admin-wrap');
	if(!container){return;}
	const target = trigger.getAttribute('data-target');
	if(target === 'jump-customized'){
		const firstCustomized = container.querySelector('details.xpressui-admin-card[data-xpressui-customized="1"]');
		if(firstCustomized){
			firstCustomized.open = true;
			firstCustomized.scrollIntoView({behavior:'smooth', block:'start'});
		}
		return;
	}
	container.querySelectorAll('details.xpressui-admin-card').forEach(function(card){
		if(target === 'all'){
			card.open = true;
		}else if(target === 'none'){
			card.open = false;
		}else if(target === 'customized'){
			card.open = card.getAttribute('data-xpressui-customized') === '1';
		}
	});
});
document.addEventListener('click', function(event){
	const clearTrigger = event.target.closest('[data-action="clear-search"]');
	if(clearTrigger && xpressuiCardSearch){
		event.preventDefault();
		xpressuiCardSearch.value = '';
		xpressuiApplyCardFilters();
		xpressuiCardSearch.focus();
		return;
	}
	const filterTrigger = event.target.closest('.xpressui-pro-filter-toggle');
	if(!filterTrigger){return;}
	event.preventDefault();
	xpressuiCustomizedOnly = !xpressuiCustomizedOnly;
	filterTrigger.classList.toggle('is-active', xpressuiCustomizedOnly);
	filterTrigger.setAttribute('aria-pressed', xpressuiCustomizedOnly ? 'true' : 'false');
	xpressuiApplyCardFilters();
});
document.addEventListener('click', function(event){
	const trigger = event.target.closest('[data-xpressui-reset-trigger]');
	if(!trigger){return;}
	event.preventDefault();
	event.stopPropagation();
	const scopeId = trigger.getAttribute('data-xpressui-reset-trigger');
	if(!scopeId){return;}
	let scope = null;
	if(scopeId.indexOf('field-') === 0){
		scope = trigger.closest('tr');
	}else{
		scope = document.querySelector('[data-xpressui-reset-scope="' + scopeId + '"]');
	}
	if(!scope){return;}
	scope.querySelectorAll('input, textarea, select').forEach(function(field){
		if(field.tagName === 'SELECT'){
			field.value = '';
		}else if(field.type === 'checkbox' || field.type === 'radio'){
			field.checked = false;
		}else{
			field.value = '';
		}
		field.dispatchEvent(new Event('input', { bubbles: true }));
		field.dispatchEvent(new Event('change', { bubbles: true }));
	});
});
xpressuiApplyCardFilters();
document.querySelectorAll('[data-xpressui-sortable]').forEach(function(list){
	let draggedItem = null;
	list.addEventListener('dragstart', function(e){
		draggedItem = e.target.closest('.xpressui-choice-row');
		if(draggedItem){
			setTimeout(function(){ draggedItem.classList.add('is-dragging'); }, 0);
		}
	});
	list.addEventListener('dragend', function(){
		if(draggedItem){
			draggedItem.classList.remove('is-dragging');
			draggedItem = null;
		}
		xpressuiSetDirtyState(true);
	});
	list.addEventListener('dragover', function(e){
		e.preventDefault();
		const draggableElements = Array.prototype.slice.call(list.querySelectorAll('.xpressui-choice-row:not(.is-dragging)'));
		const afterElement = draggableElements.reduce(function(closest, child){
			const box = child.getBoundingClientRect();
			const offset = e.clientY - box.top - box.height / 2;
			if(offset < 0 && offset > closest.offset){
				return { offset: offset, element: child };
			}else{
				return closest;
			}
		}, { offset: Number.NEGATIVE_INFINITY }).element;
		if(draggedItem){
			if(afterElement == null){
				list.appendChild(draggedItem);
			}else{
				list.insertBefore(draggedItem, afterElement);
			}
		}
	});
});
